<?php

namespace App\Exports;

use App\Models\Booking;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class BookingsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithDrawings, WithColumnWidths
{
    use Exportable;

    protected ?string $dateFrom = null;
    protected ?string $dateTo = null;
    protected ?string $status = null;
    
    // Hold collection for drawings to use
    protected $bookings;

    public function forDateRange(?string $from, ?string $to): self
    {
        $this->dateFrom = $from;
        $this->dateTo = $to;
        return $this;
    }

    public function forStatus(?string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function collection()
    {
        $query = Booking::with(['seatingSpot', 'items.menu']);

        if ($this->dateFrom) {
            $query->where('booking_date', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->where('booking_date', '<=', $this->dateTo);
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        $this->bookings = $query->orderBy('booking_date', 'desc')->get();
        return $this->bookings;
    }

    public function headings(): array
    {
        return [
            'Kode Booking',
            'Nama Pelanggan',
            'Tanggal',
            'Jumlah Tamu',
            'WhatsApp',
            'Instagram',
            'Spot Duduk',
            'Pesanan',
            'Subtotal',
            'PPN (10%)',
            'Total',
            'DP (Bukti)',
            'Status Booking',
            'Status Pembayaran',
            'Nominal Dibayar',
            'Sisa Pembayaran',
            'Catatan',
            'Dibuat',
            'Bukti Transfer (Gambar)', // Placeholder column
        ];
    }

    public function map($booking): array
    {
        // Format order items
        $orders = $booking->items->map(function ($item) {
            $options = '';
            if (!empty($item->selected_options)) {
                $options = ' (' . implode(', ', $item->selected_options) . ')';
            }
            return $item->menu->name . $options . ' x' . $item->quantity . ' = Rp ' . number_format($item->subtotal, 0, ',', '.');
        })->implode("\n");
        
        $paymentStatusFull = $booking->payment_status === 'lunas' ? 'LUNAS' : ($booking->payment_status === 'dp' ? 'DP' : '-');
        $remaining = ($booking->total_amount - ($booking->paid_amount ?? 0));
        
        return [
            $booking->booking_code,
            $booking->customer_name,
            $booking->booking_date->format('d/m/Y'),
            $booking->guest_count,
            $booking->whatsapp,
            $booking->instagram ?? '-',
            $booking->seatingSpot->name ?? '-',
            $orders,
            'Rp ' . number_format($booking->subtotal_amount ?? 0, 0, ',', '.'),
            'Rp ' . number_format($booking->tax_amount ?? 0, 0, ',', '.'),
            'Rp ' . number_format($booking->total_amount, 0, ',', '.'),
            'Rp ' . number_format($booking->dp_amount, 0, ',', '.'),
            $booking->status_label,
            $paymentStatusFull,
            $booking->paid_amount ? 'Rp ' . number_format($booking->paid_amount, 0, ',', '.') : '-',
            ($booking->payment_status === 'dp') ? 'Rp ' . number_format($remaining, 0, ',', '.') : '-',
            $booking->notes ?? '-',
            $booking->created_at->format('d/m/Y H:i'),
            '', // Empty cell for image
        ];
    }
    
    public function drawings()
    {
        $drawings = [];
        
        // Ensure collection is loaded
        if (!$this->bookings) {
            $this->collection();
        }

        foreach ($this->bookings as $index => $booking) {
            if ($booking->payment_proof) {
                $path = storage_path('app/public/' . $booking->payment_proof);
                
                if (file_exists($path)) {
                    $drawing = new Drawing();
                    $drawing->setName('Bukti Transfer');
                    $drawing->setDescription('Bukti Transfer');
                    $drawing->setPath($path);
                    $drawing->setHeight(80);
                    $drawing->setCoordinates('S' . ($index + 2)); // Column S, Row index+2 (1 for header)
                    $drawing->setOffsetX(5);
                    $drawing->setOffsetY(5);
                    
                    $drawings[] = $drawing;
                }
            }
        }

        return $drawings;
    }

    public function columnWidths(): array
    {
        return [
            'H' => 40, // Pesanan column width
            'S' => 20, // Bukti Transfer column width
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        // Set all rows height to accommodate images
        $rowCount = $this->bookings->count() + 1;
        for ($i = 2; $i <= $rowCount; $i++) {
            $sheet->getRowDimension($i)->setRowHeight(90);
        }

        // Wrap text for 'Orders' column (H)
        $sheet->getStyle('H2:H' . $rowCount)->getAlignment()->setWrapText(true);
        
        // Vertical align middle for all cells
        $sheet->getStyle('A1:S' . $rowCount)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        
        return [
            // Header row bold
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFE0E0E0'],
                ],
            ],
        ];
    }
}

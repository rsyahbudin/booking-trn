<?php

namespace App\Exports;

use App\Models\Booking;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class BookingsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    use Exportable;

    protected ?string $dateFrom = null;
    protected ?string $dateTo = null;
    protected ?string $status = null;

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

    public function query()
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

        return $query->orderBy('booking_date', 'desc');
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
            'DP',
            'Status',
            'Catatan',
            'Dibuat',
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
            $booking->notes ?? '-',
            $booking->created_at->format('d/m/Y H:i'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            // Header row bold
            1 => ['font' => ['bold' => true]],
        ];
    }
}

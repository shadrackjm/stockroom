<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Support\Money;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductExportController extends Controller
{
    /**
     * Download the (filtered) product list as a CSV file.
     *
     * The file is streamed row by row, so exporting 100,000 products
     * uses about as much memory as exporting 10.
     */
    public function __invoke(Request $request): StreamedResponse
    {
        $products = Product::query()
            ->with('category')
            ->filter($request->only(['search', 'category', 'status', 'sort', 'direction']));

        return response()->streamDownload(function () use ($products) {
            $handle = fopen('php://output', 'w');

            // escape: '' gives standard CSV quoting (PHP 8.4 requires choosing it explicitly).
            fputcsv($handle, ['SKU', 'Name', 'Category', 'Status', 'Price', 'Stock', 'Created'], escape: '');

            foreach ($products->lazy(500) as $product) {
                fputcsv($handle, array_map($this->safe(...), [
                    $product->sku,
                    $product->name,
                    $product->category?->name,
                    $product->status->label(),
                    Money::fromCents($product->price_cents),
                    $product->stock,
                    $product->created_at->toDateString(),
                ]), escape: '');
            }

            fclose($handle);
        }, 'products-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * Stop "CSV injection": Excel runs cells starting with = + - @ as formulas.
     */
    private function safe(mixed $value): mixed
    {
        return is_string($value) && preg_match('/^[=+\-@\t\r]/', $value)
            ? "'".$value
            : $value;
    }
}

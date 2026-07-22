<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionExportController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $data = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id')
            ->leftJoin('categories', 'menu_items.category_id', '=', 'categories.id')
            ->where('orders.status', 'completed')
            ->whereNull('menu_items.deleted_at') // Menangani soft deletes pada menuItem
            ->select([
                DB::raw("DATE_FORMAT(orders.created_at, '%Y-%m-%d') as transaction_date"), 
                'orders.order_number as transaction_id',
                DB::raw("COALESCE(CAST(orders.user_id AS CHAR), 'guest') as customer_id"),
                'order_items.menu_item_id as menu_id',
                'menu_items.name as menu_name',
                DB::raw("COALESCE(categories.name, 'Uncategorized') as category"),
                'order_items.quantity',
                'menu_items.base_price as price',
                'order_items.subtotal as total_price',
            ])
            ->get();

        return response()->json($data);
    }
}

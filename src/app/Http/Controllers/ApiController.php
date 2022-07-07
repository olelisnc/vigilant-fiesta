<?php

namespace App\Http\Controllers;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ApiController extends Controller
{
    public function __construct(private Builder $builder) {}

    public function generate(Request $request)
    {
        $code = Str::uuid();
        $amount =(int) $request->post('discount');

        DB::insert("insert into `voucher` (code, amount) values (?, ?)", [$code, $amount]);

        return new JsonResponse([
            'code' => $code
        ]);
    }

    public function apply(Request $request)
    {
        $voucher = DB::select("select * from `voucher` where code = ?", [$request->post('code')])[0];

        $total = 0;
        $items = $request->post('items');


        foreach ($items as $index => $item) {
            $total += $item['price'];
            $items[$index]['price_with_discount'] = $item['price']; // need starting value of price here
        }

        // If we have discount equal or more then total price then we should set all prices to 0 and return response
        if ($total <= $voucher->amount) {
            foreach ($items as $index => $item) {
                $items[$index]['price_with_discount'] = 0;
            }

            return new JsonResponse([
                'items' => $items,
                'code' => $voucher->code
            ]);
        }

        $discountLeft = $voucher->amount;
        foreach ($items as $index => $item) {
            $discount = round($item['price'] / $total * $voucher->amount);
            if ($discount >= $discountLeft) {
                $items[$index]['price_with_discount'] = $item['price'] - $discountLeft;
                $discountLeft = 0;
                break;
            }
            $items[$index]['price_with_discount'] -= $discount;
            $discountLeft -= $discount;
        }

        // Need to check edge case where we already did discount but some sum is left. Then we should run on array again
        // and get max discount from each item and stop when there is no more discount
        if ($discountLeft > 0) {
            foreach ($items as $index => $item) {
                if ($discountLeft > $item['price_with_discount']) {
                    $items[$index]['price_with_discount'] -= $item['price_with_discount'];
                    $discountLeft -= $item['price_with_discount'];
                } else {
                    $items[$index]['price_with_discount'] -= $discountLeft;
                    break;
                }
            }
        }

        return new JsonResponse([
            'items' => $items,
            'code' => $voucher->code
        ]);
    }
}

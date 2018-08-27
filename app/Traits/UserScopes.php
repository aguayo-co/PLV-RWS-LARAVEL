<?php

namespace App\Traits;

use App\CreditsTransaction;
use App\Order;
use App\Product;
use App\Sale;
use App\Traits\CreditsTransactionsSum;
use Illuminate\Support\Facades\DB;

trait UserScopes
{
    use CreditsTransactionsSum;

    /**
     * Order users by their group_ids relation.
     * Always shows users with no groups at the end.
     *
     * Can change the direction based on the lowest id of all
     * the groups a user belongs to.
     */
    public function scopeOrderedByGroup($query, $direction = 'asc')
    {
        $subQuery = DB::table('group_user')
            ->select('user_id')
            ->selectRaw('MIN(group_id) as group_id')
            ->selectRaw('MIN(group_id) IS NOT NULL as has_group')
            ->groupBy('user_id');
        return $query
            ->leftJoinSub($subQuery, 'group_user', 'group_user.user_id', '=', 'users.id')
            ->orderBy('group_user.has_group', 'desc')
            ->orderBy('group_user.group_id', $direction);
    }

    /**
     * Order users by their group_ids relation.
     * Always shows users with no groups at the end.
     *
     * Can change the direction based on the lowest id of all
     * the groups a user belongs to.
     */
    public function scopeOrderedByLatestProduct($query, $direction = 'asc')
    {
        $subQuery = DB::table('products')
            ->select('user_id')
            ->selectRaw('MAX(created_at) as created_at')
            ->groupBy('user_id');
        return $query
            ->leftJoinSub($subQuery, 'products', 'products.user_id', '=', 'users.id')
            ->orderBy('products.created_at', $direction);
    }

    /**
     * Calculate number of products purchased by the user.
     */
    public function scopeWithPurchasedProductsCount($query)
    {
        if (!$query->getQuery()->columns) {
            $query->addSelect('users.*');
        }

        $subQuery = DB::table('products')
            ->selectRaw('orders.user_id as user_id, COUNT(*) as purchased_products_count')
            ->rightJoin('product_sale', 'products.id', '=', 'product_sale.product_id')
            ->rightJoin('sales', 'product_sale.sale_id', '=', 'sales.id')
            ->rightJoin('orders', 'sales.order_id', '=', 'orders.id')
            ->where('products.status', '>', Product::STATUS_PAYMENT)
            ->whereBetween('sales.status', [Sale::STATUS_PAYED, Sale::STATUS_COMPLETED_PARTIAL])
            ->groupBy(['orders.user_id']);
        return $query->leftJoinSub(
            $subQuery,
            'ppc_sub',
            'ppc_sub.user_id',
            '=',
            'users.id'
        )
        ->addSelect('purchased_products_count');
    }

    /**
     * Calculate available credits, including the ones being used
     * on the current shopping cart.
     */
    public function scopeWithCredits($query)
    {
        if (!$query->getQuery()->columns) {
            $query->addSelect('users.*');
        }

        $query->leftJoin('credits_transactions', 'credits_transactions.user_id', '=', 'users.id');

        $this->setActiveCreditsTransactionsConditions($query);

        $query->selectRaw('CAST(SUM(credits_transactions.amount) AS SIGNED) credits')
            ->selectRaw('CAST(SUM(credits_transactions.commission) AS SIGNED) commissions')
            ->groupBy(['users.id']);
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\I18n;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Repositories\UserRepository;

/**
 * Seeds localized Belgian festive sample products and orders for new accounts,
 * and allows wiping all demo rows in one click.
 */
final class DemoSeederService
{
    private ProductRepository $products;
    private OrderRepository $orders;
    private UserRepository $users;
    private I18n $i18n;

    public function __construct(
        ProductRepository $products,
        OrderRepository $orders,
        UserRepository $users,
        I18n $i18n
    ) {
        $this->products = $products;
        $this->orders = $orders;
        $this->users = $users;
        $this->i18n = $i18n;
    }

    public function seedForUser(int $userId): void
    {
        $p1 = $this->products->create(
            $userId,
            $this->i18n->t('demo.prod_1_name'),
            $this->i18n->t('demo.prod_1_cat'),
            $this->i18n->t('demo.prod_1_unit'),
            58.00,
            true,
            true
        );
        $p2 = $this->products->create(
            $userId,
            $this->i18n->t('demo.prod_2_name'),
            $this->i18n->t('demo.prod_2_cat'),
            $this->i18n->t('demo.prod_2_unit'),
            24.50,
            true,
            true
        );
        $p3 = $this->products->create(
            $userId,
            $this->i18n->t('demo.prod_3_name'),
            $this->i18n->t('demo.prod_3_cat'),
            $this->i18n->t('demo.prod_3_unit'),
            22.00,
            true,
            true
        );
        $p4 = $this->products->create(
            $userId,
            $this->i18n->t('demo.prod_4_name'),
            $this->i18n->t('demo.prod_4_cat'),
            $this->i18n->t('demo.prod_4_unit'),
            36.00,
            true,
            true
        );
        $p5 = $this->products->create(
            $userId,
            $this->i18n->t('demo.prod_5_name'),
            $this->i18n->t('demo.prod_5_cat'),
            $this->i18n->t('demo.prod_5_unit'),
            14.00,
            true,
            true
        );
        $p6 = $this->products->create(
            $userId,
            $this->i18n->t('demo.prod_6_name'),
            $this->i18n->t('demo.prod_6_cat'),
            $this->i18n->t('demo.prod_6_unit'),
            29.00,
            true,
            true
        );

        $this->orders->createWithItems($userId, [
            'customer_name' => 'Fam. Peeters',
            'customer_phone' => '0475 12 34 56',
            'customer_email' => 'peeters@voorbeeld.be',
            'pickup_date' => '2026-12-24',
            'pickup_slot' => '10:00 - 11:00',
            'status' => 'ready',
            'payment_status' => 'paid',
            'deposit_amount' => 261.00,
            'notes' => $this->i18n->t('demo.order_1_note'),
            'is_demo' => true,
        ], [
            [
                'product_id' => $p1,
                'product_name' => $this->i18n->t('demo.prod_1_name'),
                'unit' => $this->i18n->t('demo.prod_1_unit'),
                'quantity' => 4,
                'unit_price' => 58.00,
            ],
            [
                'product_id' => $p6,
                'product_name' => $this->i18n->t('demo.prod_6_name'),
                'unit' => $this->i18n->t('demo.prod_6_unit'),
                'quantity' => 1,
                'unit_price' => 29.00,
            ],
        ]);

        $this->orders->createWithItems($userId, [
            'customer_name' => 'De Smet BV',
            'customer_phone' => '011 45 67 89',
            'customer_email' => 'onthaal@desmet-bv.be',
            'pickup_date' => '2026-12-24',
            'pickup_slot' => '11:00 - 12:00',
            'status' => 'confirmed',
            'payment_status' => 'deposit',
            'deposit_amount' => 50.00,
            'notes' => $this->i18n->t('demo.order_2_note'),
            'is_demo' => true,
        ], [
            [
                'product_id' => $p2,
                'product_name' => $this->i18n->t('demo.prod_2_name'),
                'unit' => $this->i18n->t('demo.prod_2_unit'),
                'quantity' => 2,
                'unit_price' => 24.50,
            ],
            [
                'product_id' => $p5,
                'product_name' => $this->i18n->t('demo.prod_5_name'),
                'unit' => $this->i18n->t('demo.prod_5_unit'),
                'quantity' => 4,
                'unit_price' => 14.00,
            ],
        ]);

        $this->orders->createWithItems($userId, [
            'customer_name' => 'Claesen An',
            'customer_phone' => '0498 76 54 32',
            'customer_email' => '',
            'pickup_date' => '2026-12-31',
            'pickup_slot' => '13:00 - 15:00',
            'status' => 'new',
            'payment_status' => 'unpaid',
            'deposit_amount' => 0.00,
            'notes' => $this->i18n->t('demo.order_3_note'),
            'is_demo' => true,
        ], [
            [
                'product_id' => $p3,
                'product_name' => $this->i18n->t('demo.prod_3_name'),
                'unit' => $this->i18n->t('demo.prod_3_unit'),
                'quantity' => 6,
                'unit_price' => 22.00,
            ],
            [
                'product_id' => $p4,
                'product_name' => $this->i18n->t('demo.prod_4_name'),
                'unit' => $this->i18n->t('demo.prod_4_unit'),
                'quantity' => 2,
                'unit_price' => 36.00,
            ],
        ]);

        $this->users->setHasDemoData($userId, true);
    }

    public function clearForUser(int $userId): void
    {
        $this->orders->deleteDemoForUser($userId);
        $this->products->deleteDemoForUser($userId);
        $this->users->setHasDemoData($userId, false);
    }
}

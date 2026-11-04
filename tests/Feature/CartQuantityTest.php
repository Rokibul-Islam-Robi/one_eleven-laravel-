<?php

namespace Tests\Feature;

use App\Support\Catalog;
use Tests\TestCase;

class CartQuantityTest extends TestCase
{
    public function test_guest_cart_quantity_changes_are_persisted_and_returned_in_the_cart_views(): void
    {
        $this->withSession(['cart' => ['1' => 1]]);
        $unitPrice = Catalog::variantUnitPrice(Catalog::byId('1'), '', 1);

        foreach ([2, 3, 2] as $qty) {
            $response = $this->postJson('/api/cart.php', [
                'action' => 'qty',
                'id' => '1',
                'qty' => $qty,
            ]);

            $response->assertOk()
                ->assertJsonPath('ok', true)
                ->assertJsonPath('count', $qty)
                ->assertSessionHas('cart.1', $qty);

            $expectedTotal = round($unitPrice * $qty, 2);
            $this->assertEquals($expectedTotal, $response->json('subtotal'));
            $this->assertStringContainsString(
                '<span class="dr-num" aria-live="polite">' . $qty . '</span>',
                $response->json('body')
            );
            $this->assertStringContainsString('data-price="' . (int) $expectedTotal . '"', $response->json('foot'));
            $this->assertStringContainsString('data-price="' . (int) $expectedTotal . '"', $response->json('page'));
            $this->assertStringContainsString('name="qty" value="' . $qty . '"', $response->json('page'));
        }

        $this->get('/cart')
            ->assertOk()
            ->assertSee('name="qty" value="2"', false);
    }

    public function test_quantity_cannot_be_decreased_below_one_or_used_to_remove_an_item(): void
    {
        $this->withSession(['cart' => ['1' => 1]]);

        $this->postJson('/api/cart.php', [
            'action' => 'qty',
            'id' => '1',
            'qty' => 1,
        ])->assertOk()->assertJsonPath('count', 1);

        $this->postJson('/api/cart.php', [
            'action' => 'qty',
            'id' => '1',
            'qty' => 0,
        ])->assertUnprocessable()->assertJsonPath('ok', false)
            ->assertSessionHas('cart.1', 1);
    }

    public function test_quantity_update_keeps_other_products_independent(): void
    {
        $this->withSession(['cart' => ['1' => 1, '2' => 2]]);

        $this->postJson('/api/cart.php', [
            'action' => 'qty',
            'id' => '1',
            'qty' => 3,
        ])->assertOk()
            ->assertJsonPath('count', 5)
            ->assertSessionHas('cart.1', 3)
            ->assertSessionHas('cart.2', 2);
    }

    public function test_cart_mutations_are_not_accepted_over_get(): void
    {
        $this->withSession(['cart' => ['1' => 1]])
            ->get('/api/cart.php?action=qty&id=1&qty=2')
            ->assertStatus(405)
            ->assertSessionHas('cart.1', 1);
    }

    public function test_cart_status_get_returns_the_current_session_cart(): void
    {
        $response = $this->withSession(['cart' => ['1' => 2]])
            ->getJson('/api/cart.php?action=status');

        $response
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('count', 2);
        $this->assertEquals(
            round(Catalog::variantUnitPrice(Catalog::byId('1'), '', 1) * 2, 2),
            $response->json('subtotal')
        );
        $this->assertStringContainsString('>2</span>', $response->json('body'));
    }
}

<?php

namespace Tests\Feature;

use App\Models\Banner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BannerTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_for_returns_only_active_in_schedule_for_placement_ordered_by_priority(): void
    {
        $high = Banner::create(['placement' => 'home', 'image' => 'assets/banners/a.png', 'is_active' => true, 'priority' => 10]);
        $low = Banner::create(['placement' => 'home', 'image' => 'assets/banners/b.png', 'is_active' => true, 'priority' => 1]);
        Banner::create(['placement' => 'home', 'image' => 'assets/banners/c.png', 'is_active' => false, 'priority' => 99]); // inactive
        Banner::create(['placement' => 'explore', 'image' => 'assets/banners/d.png', 'is_active' => true, 'priority' => 99]); // other placement
        Banner::create(['placement' => 'home', 'image' => 'assets/banners/e.png', 'is_active' => true, 'priority' => 99, 'start_date' => now()->addDay()]); // future
        Banner::create(['placement' => 'home', 'image' => 'assets/banners/f.png', 'is_active' => true, 'priority' => 99, 'end_date' => now()->subDay()]); // expired

        $result = Banner::activeFor('home');

        $this->assertEquals([$high->id, $low->id], $result->pluck('id')->all());
    }

    public function test_home_page_renders_active_home_banner(): void
    {
        Banner::create(['placement' => 'home', 'image' => 'assets/banners/live-banner.png', 'is_active' => true, 'priority' => 5, 'redirect_link' => '/explore']);

        $this->get('/')
            ->assertOk()
            ->assertSee('assets/banners/live-banner.png');
    }
}

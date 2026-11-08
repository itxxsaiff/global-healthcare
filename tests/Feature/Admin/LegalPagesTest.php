<?php

namespace Tests\Feature\Admin;

use App\Models\Page;
use App\Models\User;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    public function test_admin_edits_a_legal_page_with_the_rich_text_editor(): void
    {
        $admin = User::factory()->admin()->create();
        $page = Page::query()->firstOrFail();

        $this->actingAs($admin)->get(route('admin.pages.index'))
            ->assertOk()
            ->assertSee('data-rich-editor="body-'.$page->id.'"', false);

        $html = '<h2>Your rights</h2><p>You can <strong>ask</strong> us to <em>delete</em> your data.</p>'
            .'<ul><li>Access</li><li>Correction</li></ul><script>alert(1)</script><p onclick="x()">Hi</p>';

        $this->actingAs($admin)->put(route('admin.pages.update', $page), [
            'title' => 'Privacy Policy',
            'meta_description' => 'How we handle your data.',
            'body' => $html,
        ])->assertRedirect();

        $body = $page->fresh()->body;
        $this->assertStringContainsString('<h2>Your rights</h2>', $body);
        $this->assertStringContainsString('<ul><li>Access</li><li>Correction</li></ul>', $body);
        $this->assertStringContainsString('<strong>ask</strong>', $body);
        $this->assertStringNotContainsString('<script', $body);
        $this->assertStringNotContainsString('onclick', $body);

        $this->get(route('pages.show', $page))->assertOk()->assertSee('<h2>Your rights</h2>', false);
    }
}

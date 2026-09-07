<?php

namespace Tests\Unit\Admin;

use App\Models\PortalApp;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class AppFormViewTest extends TestCase
{
    public function test_form_view_renders_without_app_variable(): void
    {
        view()->share('errors', new ViewErrorBag);

        $html = view('Admin::apps._form', [
            'icons' => [],
            'users' => collect(),
        ])->render();

        $this->assertStringContainsString('id="title"', $html);
        $this->assertStringContainsString('name="title"', $html);
    }

    public function test_form_view_renders_with_portal_app_instance(): void
    {
        view()->share('errors', new ViewErrorBag);

        $app = new PortalApp(['title' => 'Sample App Title']);

        $html = view('Admin::apps._form', [
            'icons' => [],
            'users' => collect(),
            'app' => $app,
        ])->render();

        $this->assertStringContainsString('value="Sample App Title"', $html);
    }
}

<?php

namespace Tests\Unit;

use App\Support\ModuleContextRedirect;
use PHPUnit\Framework\TestCase;

class ModuleContextRedirectTest extends TestCase
{
    public function test_livewire_url_is_invalid(): void
    {
        $this->assertFalse(ModuleContextRedirect::isValidModuleUrl('/livewire/update'));
        $this->assertFalse(ModuleContextRedirect::isValidModuleUrl('/livewire/message/some-component'));
    }

    public function test_auth_urls_are_invalid(): void
    {
        $this->assertFalse(ModuleContextRedirect::isValidModuleUrl('/login'));
        $this->assertFalse(ModuleContextRedirect::isValidModuleUrl('/register'));
        $this->assertFalse(ModuleContextRedirect::isValidModuleUrl('/profile'));
    }

    public function test_module_url_is_valid(): void
    {
        $this->assertTrue(ModuleContextRedirect::isValidModuleUrl('/some-module/dashboard'));
        $this->assertTrue(ModuleContextRedirect::isValidModuleUrl('/admin/overview'));
    }

    public function test_empty_or_root_url_is_invalid(): void
    {
        $this->assertFalse(ModuleContextRedirect::isValidModuleUrl(null));
        $this->assertFalse(ModuleContextRedirect::isValidModuleUrl(''));
        $this->assertFalse(ModuleContextRedirect::isValidModuleUrl('/'));
    }
}

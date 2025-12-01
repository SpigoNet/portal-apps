<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\VoltServiceProvider::class,
    App\Modules\Admin\AdminServiceProvider::class,
    App\Modules\DspaceForms\DspaceFormsServiceProvider::class,
    App\Modules\TreeTask\TreeTaskServiceProvider::class,
    App\Modules\ANT\ANTServiceProvider::class,
    App\Modules\EnvioWhatsapp\EnvioWhatsappServiceProvider::class,
    App\Modules\GestorHoras\GestorHorasServiceProvider::class,
];

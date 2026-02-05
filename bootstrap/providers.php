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
    App\Modules\Metricas\MetricasServiceProvider::class,
    App\Modules\Mithril\MithrilServiceProvider::class,
    App\Modules\MundosDeMim\MundosDeMimServiceProvider::class,
    App\Modules\StreamingManager\StreamingManagerServiceProvider::class,
    App\Modules\BolaoReuniao\BolaoReuniaoServiceProvider::class,
];


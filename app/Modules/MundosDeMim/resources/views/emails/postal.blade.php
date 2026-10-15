<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>Seu postal de hoje chegou</title>
</head>
<body style="margin:0;padding:0;background-color:#14122B;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#14122B;">
    <tr>
        <td align="center" style="padding:24px 12px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;">
                <tr>
                    <td style="padding:8px 8px 20px;font-family:Arial,Helvetica,sans-serif;font-size:22px;font-weight:bold;color:#F3F0FB;">
                        <span style="color:#FF7A59;">&#9679;</span> mundos de mim
                    </td>
                </tr>
                <tr>
                    <td style="background-color:#1E1B3F;border-radius:24px;padding:28px 24px;font-family:Arial,Helvetica,sans-serif;">
                        <p style="margin:0 0 6px;font-size:13px;letter-spacing:1px;text-transform:uppercase;color:#A79BFF;font-weight:bold;">
                            Postal de hoje{{ $mundo ? ' · '.$mundo : '' }}
                        </p>
                        <h1 style="margin:0 0 20px;font-size:28px;line-height:1.15;color:#F3F0FB;">
                            {{ $primeiroNome !== '' ? $primeiroNome.', seu' : 'Seu' }} postal chegou.
                        </h1>

                        <a href="{{ $imageUrl }}" target="_blank" style="text-decoration:none;">
                            <img src="{{ $imageUrl }}" alt="{{ $mundo ?? 'Seu postal de hoje' }}" width="552" style="display:block;width:100%;max-width:552px;height:auto;border:5px solid #F3F0FB;border-radius:18px;box-sizing:border-box;">
                        </a>

                        <div style="margin:24px 0 0;font-size:17px;line-height:1.65;color:#E6E3F5;">
                            @foreach(preg_split('/\n{2,}/', trim($story)) as $paragrafo)
                                <p style="margin:0 0 14px;">{!! nl2br(e($paragrafo)) !!}</p>
                            @endforeach
                        </div>

                        <table role="presentation" cellpadding="0" cellspacing="0" style="margin:8px 0 4px;">
                            <tr>
                                <td style="background-color:#FF7A59;border-radius:999px;">
                                    <a href="{{ $imageUrl }}" target="_blank" style="display:inline-block;padding:14px 24px;font-size:15px;font-weight:bold;color:#14122B;text-decoration:none;">Ver em tamanho grande</a>
                                </td>
                                <td style="width:12px;"></td>
                                <td>
                                    <a href="{{ $atlasUrl }}" target="_blank" style="display:inline-block;padding:13px 22px;font-size:15px;font-weight:bold;color:#F3F0FB;text-decoration:none;border:1px solid #6a64b0;border-radius:999px;">Abrir meu atlas</a>
                                </td>
                            </tr>
                        </table>

                        @if(count($notas))
                            <div style="margin-top:28px;padding-top:20px;border-top:1px solid #3a366b;text-align:center;">
                                <p style="margin:0 0 8px;font-size:15px;color:#B4B0D4;">Que nota você dá para este postal?</p>
                                <p style="margin:0;font-size:34px;line-height:1;">
                                    @foreach($notas as $nota => $url)
                                        <a href="{{ $url }}" title="{{ $nota }} de 5" style="text-decoration:none;color:#FFB48A;padding:0 6px;">&#9733;</a>
                                    @endforeach
                                </p>
                                <p style="margin:10px 0 0;font-size:12px;color:#8F8BB8;">Ao avaliar, você entra automaticamente no seu atlas.</p>
                            </div>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td align="center" style="padding:20px 8px;font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#8F8BB8;">
                        Um postal novo, todo dia · Mundos de Mim
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>

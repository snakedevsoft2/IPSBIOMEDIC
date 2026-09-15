<?php
declare(strict_types=1);

use Dompdf\Dompdf;
use Dompdf\Options;

final class Pdf
{
    public static function render(string $view, array $data, string $filename, string $orientation = 'portrait'): never
    {
        foreach (['storage/tmp', 'storage/fonts'] as $dir) {
            if (!is_dir(BASE_PATH . '/' . $dir)) {
                mkdir(BASE_PATH . '/' . $dir, 0755, true);
            }
        }

        $data['logo'] = image_data_uri(BASE_PATH . '/' . logo_path('claro'));
        $html = render_template(BASE_PATH . '/app/views/pdf/' . $view . '.php', $data);

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('tempDir', BASE_PATH . '/storage/tmp');
        $options->set('fontCache', BASE_PATH . '/storage/fonts');
        $options->setChroot([BASE_PATH]);

        $pdf = new Dompdf($options);
        $pdf->loadHtml($html, 'UTF-8');
        $pdf->setPaper('letter', $orientation);
        $pdf->render();

        $canvas = $pdf->getCanvas();
        $font = $pdf->getFontMetrics()->getFont('DejaVu Sans');
        $w = $canvas->get_width();
        $h = $canvas->get_height();
        $gris = [0.39, 0.45, 0.53];
        $legal = setting('pie_reportes', 'Documento confidencial sujeto a reserva legal (Ley 23 de 1981 y Resolución 1995 de 1999).');
        $pie = 'Impreso: ' . date('d/m/Y h:i a') . ' por ' . (user() ? nombre_usuario(user()) : '');
        $canvas->page_line(36, $h - 44, $w - 36, $h - 44, [0.85, 0.87, 0.9], 0.6);
        $canvas->page_text(36, $h - 38, mb_substr($legal, 0, 160), $font, 6.5, $gris);
        $canvas->page_text(36, $h - 28, $pie, $font, 6.5, $gris);
        $canvas->page_text($w - 100, $h - 28, 'Página {PAGE_NUM} de {PAGE_COUNT}', $font, 6.5, $gris);

        $pdf->stream($filename, ['Attachment' => false]);
        exit;
    }
}

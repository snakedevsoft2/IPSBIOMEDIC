<?php
declare(strict_types=1);

final class PdfController extends Controller
{
    private function bundle(): array
    {
        $b = Clinica::consulta($this->id()) ?? abort(404, 'Atención no encontrada.');
        if ($b['c']['estado'] !== 'cerrada') {
            abort(403, 'La historia clínica debe estar finalizada para imprimir documentos.');
        }
        $b['firma'] = $b['c']['med_firma'] ? image_data_uri(BASE_PATH . '/' . $b['c']['med_firma']) : '';
        return $b;
    }

    private function nombreArchivo(string $prefijo, array $b): string
    {
        return $prefijo . '-' . preg_replace('/[^A-Za-z0-9]/', '', $b['p']['numero_documento']) . '-' . $b['c']['id'] . '.pdf';
    }

    public function historia(): void
    {
        $b = $this->bundle();
        Audit::log('pdf_historia', 'consultas', (int) $b['c']['id']);
        Pdf::render('historia', $b, $this->nombreArchivo('HistoriaClinica', $b));
    }

    public function formula(): void
    {
        $b = $this->bundle();
        if (!$b['items']) {
            abort(404, 'Esta atención no tiene fórmula médica.');
        }
        Audit::log('pdf_formula', 'consultas', (int) $b['c']['id']);
        Pdf::render('formula', $b, $this->nombreArchivo('FormulaMedica', $b));
    }

    public function ordenes(): void
    {
        $b = $this->bundle();
        if (!$b['ordenes']) {
            abort(404, 'Esta atención no tiene órdenes.');
        }
        Audit::log('pdf_ordenes', 'consultas', (int) $b['c']['id']);
        Pdf::render('ordenes', $b, $this->nombreArchivo('Ordenes', $b));
    }

    public function incapacidad(): void
    {
        $b = $this->bundle();
        if (!$b['c']['incapacidad_dias']) {
            abort(404, 'Esta atención no tiene incapacidad.');
        }
        Audit::log('pdf_incapacidad', 'consultas', (int) $b['c']['id']);
        Pdf::render('incapacidad', $b, $this->nombreArchivo('Incapacidad', $b));
    }
}

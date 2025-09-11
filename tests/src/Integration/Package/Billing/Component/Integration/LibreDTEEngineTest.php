<?php

use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\Component\Document\Support\DocumentBag;
use PHPUnit\Framework\TestCase;

class LibreDTEEngineTest extends TestCase
{
    public function test_pdf_generated_from_xml_fixture()
    {
        $engine = new class {
            function cleanXml(string $xml): string {
                // Eliminar BOM si existe
                if (substr($xml, 0, 3) === "\xEF\xBB\xBF") {
                    $xml = substr($xml, 3);
                }

                // Convertir a UTF-8 si es ISO-8859-1
                if (stripos($xml, 'encoding="ISO-8859-1"') !== false) {
                    $xml = mb_convert_encoding($xml, 'UTF-8', 'ISO-8859-1');
                    $xml = str_replace('encoding="ISO-8859-1"', 'encoding="UTF-8"', $xml);
                }

                // Eliminar caracteres invisibles no válidos
                $xml = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $xml);

                // Reescribir cabecera a UTF-8 por seguridad
                $xml = preg_replace('/<\?xml.*encoding=["\'].*?["\'].*?\?>/i', '<?xml version="1.0" encoding="UTF-8"?>', $xml);

                return $xml;
            }

            public function render_pdf($xml_or_signed_xml, array $settings)
            {
                $app = Application::getInstance('prod', true);

                $xml = mb_convert_encoding($xml_or_signed_xml, 'UTF-8', 'ISO-8859-1');
                $xml = str_replace('encoding="ISO-8859-1"', 'encoding="UTF-8"', $xml);

                $xml = $this->cleanXml($xml);
                $billing = $app->getPackageRegistry()->getPackage('billing');
                $document = $billing->getDocumentComponent();


                $loader = $document->getLoaderWorker();
                $bag = $loader->loadXml($xml);

                $renderer = $document->getRendererWorker();
                $pdfContent = $renderer->render($bag); // <- Llama a tu método

                if (!$pdfContent || !is_string($pdfContent)) {
                    return false;
                }

                $sx = new \SimpleXMLElement($xml_or_signed_xml);
                $doc = $sx->Documento ?: $sx;
                $tipo  = (string)($doc->Encabezado->IdDoc->TipoDTE ?? 'DTE');
                $folio = (string)($doc->Encabezado->IdDoc->Folio ?? '0');
                $rut   = (string)($doc->Encabezado->Receptor->RUTRecep ?? 'SIN-RUT');

                $baseDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'dte_output' . DIRECTORY_SEPARATOR;
                if (!is_dir($baseDir)) {
                    mkdir($baseDir, 0755, true);
                }

                $file = 'DTE_' . $tipo . '_' . $folio . '_' . time() . '.pdf';
                $path = $baseDir . $file;
                file_put_contents($path, $pdfContent);

                return $path;
            }
        };

        $xml = file_get_contents(__DIR__ . '/boleta_multidetalle.xml');
        $this->assertNotFalse($xml);

        $pdfPath = $engine->render_pdf($xml, []);
        $this->assertIsString($pdfPath);
        $this->assertFileExists($pdfPath);

        $content = file_get_contents($pdfPath);
        $this->assertStringStartsWith('%PDF', $content);

        $outputDir = __DIR__ . '/output/';
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $outputFile = $outputDir . basename($pdfPath);
        copy($pdfPath, $outputFile);

        $this->assertFileExists($outputFile);
    }
}

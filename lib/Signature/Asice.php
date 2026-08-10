<?php

namespace OCA\ElectronicSignatures\Signature;

class Asice
{
    public function addSignatureAsice(string $containerFile, string $xadesSignature): string
    {
        $tempZipFile = tempnam(sys_get_temp_dir(), 'signature');
        file_put_contents($tempZipFile, $containerFile);

        $signature = new \DOMDocument();
        $signature->loadXML($xadesSignature);
        $node = $signature->firstChild;
        $node = $node->firstChild;

        $signatureId = $node->getAttribute('Id');

        $zip = new \ZipArchive();

        $zip->open($tempZipFile);
        $zip->addFromString("META-INF/signatures$signatureId.xml", $xadesSignature);
        $zip->close();

        return file_get_contents($tempZipFile);
    }

    public function createAsiceContainer(array $files): string
    {
        $zip = new \ZipArchive();
        $tempZipFile = tempnam(sys_get_temp_dir(), 'eideasy');

        $zip->open($tempZipFile, \ZipArchive::OVERWRITE);
        $zip->addFromString('mimetype', 'application/vnd.etsi.asic-e+zip');
        $zip->addEmptyDir('META-INF');

        $manifestTemplate = <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="no" ?>
<manifest:manifest xmlns:manifest="urn:oasis:names:tc:opendocument:xmlns:manifest:1.0" manifest:version="1.2">
  <manifest:file-entry manifest:full-path="/" manifest:media-type="application/vnd.etsi.asic-e+zip"/>
</manifest:manifest>
XML;

        $manifest = simplexml_load_string($manifestTemplate);
        $namespace = 'urn:oasis:names:tc:opendocument:xmlns:manifest:1.0';

        foreach ($files as $fileData) {
            $name = $fileData['fileName'];
            $fileContent = $fileData['fileContent'];

            $zip->addFromString($name, $fileContent);

            $newFileEntry = $manifest->addChild('file-entry');
            $newFileEntry->addAttribute('manifest:full-path', $name, $namespace);
            $xmlMime = $fileData['mimeType'];
            $newFileEntry->addAttribute('manifest:media-type', $xmlMime, $namespace);
        }

        $zip->addFromString('META-INF/manifest.xml', $manifest->asXML());
        $zip->close();

        return file_get_contents($tempZipFile);
    }
}

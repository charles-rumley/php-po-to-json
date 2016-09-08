Po To Json Converter
=========

Convert PO files to Jed-compatible JSON using PHP.

Inspired by:
    - github.com/mikeedwards/po2json

Installation
=====
    
    composer require charles-rumley/php-po-to-json

Usage
=====

    use CharlesRumley\PoToJson;
    
    $poToJson = new PoToJson();
    
    // Convert a PO file to JSON
    $rawJson = $poToJson->withPoFile($path)->toRawJson();
    
    // Convert a PO file to Jed-compatible JSON
    $jedJson = $poToJson->withPoFile($path)->toJedJson();

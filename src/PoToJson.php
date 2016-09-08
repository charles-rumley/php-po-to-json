<?php namespace CharlesRumley;

use Sepia\PoParser;
use Sepia\StringHandler;

class PoToJson
{

    private $poFileContents;

    public function __construct($poFileContents = null)
    {
        $this->poFileContents = $poFileContents;
    }

    public function withPoFile($path)
    {
        return new self(file_get_contents($path));
    }

    public function withPoFileContents($string)
    {
        return new self($string);
    }

    public function toRawJson($fuzzy = false)
    {
        $poParser = new PoParser(new StringHandler($this->poFileContents));
        $entries = $poParser->parse();
        $headers = $this->parseHeaders($poParser->getHeaders());

        return json_encode($this->convertToGettextCompatibleTranslations($headers, $entries, $fuzzy));
    }

    public function toJedJson($fuzzy = false, $domain = 'messages')
    {
        $poParser = new PoParser(new StringHandler($this->poFileContents));
        $entries = $poParser->parse();
        $headers = $this->parseHeaders($poParser->getHeaders());
        $gettextCompatibleJson = $this->convertToGettextCompatibleTranslations($headers, $entries, $fuzzy);

        return json_encode($this->convertGettextTranslationsToJedSpec($domain, $gettextCompatibleJson));
    }

    private function parseHeaders($headers)
    {
        foreach ($headers as &$h) {
            $h = trim($h, "\"\n");
        }
        $raw = implode("", $headers);
        $raw = str_replace('\n', "\n", $raw);
        return $this->parse_http_headers($raw);
    }

    /**
     * From http://stackoverflow.com/a/20933560/682317
     * @param $raw_headers
     * @return array
     */
    private function parse_http_headers($raw_headers)
    {
        $headers = array();
        $key = '';
        foreach (explode("\n", $raw_headers) as $i => $h) {
            $h = explode(':', $h, 2);
            if (isset($h[1])) {
                if (!isset($headers[$h[0]])) {
                    $headers[$h[0]] = trim($h[1]);
                } elseif (is_array($headers[$h[0]])) {
                    $headers[$h[0]] = array_merge($headers[$h[0]], array(trim($h[1])));
                } else {
                    $headers[$h[0]] = array_merge(array($headers[$h[0]]), array(trim($h[1])));
                }
                $key = $h[0];
            } else {
                if (substr($h[0], 0, 1) == "\t") {
                    $headers[$key] .= "\r\n\t" . trim($h[0]);
                } elseif (!$key) {
                    $headers[0] = trim($h[0]);
                }
            }
        }
        return $headers;
    }

    private function convertToGettextCompatibleTranslations(
        $_headers,
        $translations,
        $fuzzy = false
    ) {
        $headers = array();
        foreach ($_headers as $key => $value) {
            $key = strtolower($key);
            $headers[$key] = $value;
        }
        // Attach headers (overwrites any empty translation keys that may have somehow gotten in)
        $result[""] = $headers;
        // Create gettext/Jed compatible JSON from parsed data
        foreach ($translations as $translationKey => $t) {
            $entry = array();
            if (isset($t["msgid_plural"])) {
                $entry[0] = $t["msgid_plural"][0];
                $entry[1] = $t["msgstr[0]"][0];
                isset($t["msgstr[1]"]) ? ($entry[2] = $t["msgstr[1]"][0]) : null;
                isset($t["msgstr[2]"]) ? ($entry[3] = $t["msgstr[2]"][0]) : null;
            } else {
                $entry[0] = null;
                $entry[1] = implode("", $t["msgstr"]);
            }
            // msg id json format
            if ($t["msgid"][0] == '' && isset($t["msgid"][1])) {
                array_shift($t["msgid"]);
                $msgid = implode("", $t["msgid"]);
            } else {
                $msgid = implode("", $t["msgid"]);
            }
            // json object key based on msd id and context
            if (isset($t["msgctxt"][0])) {
                $key = $t["msgctxt"][0] . json_decode('"' . '\u0004' . '"') . $msgid;
            } else {
                $key = $msgid;
            }

            if (!$fuzzy && $this->isFuzzy($t)) {
                continue;
            }
            $result[$key] = $entry;
        }

        return $result;
    }

    private function convertGettextTranslationsToJedSpec($domain, $translations)
    {
        $jedCompatibleTranslations = [
            "domain" => $domain,
            "locale_data" => [],
        ];

        // Jed 1.x compatibility
        // todo refactor this out to allow Jed < 1.x compatibility
        $translations = array_map(
            function ($translation) {
                if (!empty($translation)) {
                    for ($i = 2; $i < count($translation); $i++) {
                        if (isset($translation[$i]) && empty($translation[$i])) {
                            $translation[$i] = $translation[0];
                        }
                    }
                    array_shift($translation);
                }

                return $translation;
            },
            $translations
        );
        $jedCompatibleTranslations["locale_data"][$domain] = $translations;
        $jedCompatibleTranslations["locale_data"][$domain][""] = [
            "domain" => $domain,
            "plural_forms" => isset($translations[""]["plural-forms"]) ? $translations[""]["plural-forms"] : null,
            "lang" => $translations[""]["language"],
        ];

        return $jedCompatibleTranslations;
    }

    private function isFuzzy($translation)
    {
        if (isset($translation['flags'])) {
            $flags = $translation['flags'];
            foreach ($flags as $index => $flag) {
                if ($flag == 'fuzzy') {
                    return true;
                }
            }
        }
        return false;
    }

} 
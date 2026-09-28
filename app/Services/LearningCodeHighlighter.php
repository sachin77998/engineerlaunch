<?php
namespace App\Services;

use Illuminate\Support\HtmlString;

final class LearningCodeHighlighter
{
    /** Presentation only: never evaluate or execute lesson code. */
    public function render(string $source, string $language = 'sql'): HtmlString
    {
        $keywords = $language === 'php'
            ? 'function|use|return|if|else|foreach|as|throw|new|true|false|null|public|private|class'
            : 'SELECT|FROM|WHERE|FOR|UPDATE|INSERT|INTO|VALUES|SET|CREATE|TABLE|ALTER|ADD|CONSTRAINT|UNIQUE|PRIMARY|KEY|FOREIGN|REFERENCES|NOT|NULL|DEFAULT|CHECK|ENGINE|START|TRANSACTION|COMMIT|ROLLBACK|AND|OR|AS|CASE|WHEN|THEN|ELSE|END|GROUP|BY|ORDER|LIMIT|SUM|COUNT|BEGIN|DECIMAL|BIGINT|VARCHAR|TIMESTAMP|AUTO_INCREMENT|SIGNAL|SQLSTATE|IF|CALL|PROCEDURE|DECLARE|EXIT|HANDLER|RESIGNAL|DELIMITER';
        $pattern = '~(?<comment>--[^\r\n]*|//[^\r\n]*|/\*[\s\S]*?\*/)|(?<string>\'(?:\'\'|\\\\.|[^\'\\\\])*\'|"(?:\\\\.|[^"\\\\])*")|(?<keyword>\b(?:'.$keywords.')\b)|(?<number>\b\d+(?:\.\d+)?\b)~i';
        preg_match_all($pattern, $source, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
        $html = ''; $offset = 0;
        foreach ($matches as $match) {
            [$token, $start] = $match[0];
            $html .= e(substr($source, $offset, $start - $offset));
            $kind = 'keyword';
            foreach (['comment','string','keyword','number'] as $candidate) {
                if (isset($match[$candidate]) && $match[$candidate][1] >= 0 && $match[$candidate][0] !== '') { $kind = $candidate; break; }
            }
            $html .= '<span class="sql-token-'.$kind.'">'.e($token).'</span>';
            $offset = $start + strlen($token);
        }
        return new HtmlString($html.e(substr($source, $offset)));
    }
}

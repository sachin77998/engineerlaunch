<?php
namespace App\Services;

use Illuminate\Support\HtmlString;

final class LearningCodeHighlighter
{
    /** Presentation only: never evaluate or execute lesson code. */
    public function render(string $source, string $language = 'sql'): HtmlString
    {
        $keywords = match ($language) {
            'php' => 'function|use|return|if|else|foreach|as|throw|new|true|false|null|public|private|class',
            'java' => 'public|private|protected|class|interface|return|if|else|for|new|throw|throws|try|catch|final|void|static|extends|implements|import|package|true|false|null',
            'python', 'py' => 'def|return|import|from|as|for|in|if|elif|else|while|class|with|try|except|lambda|None|True|False|print',
            'js', 'javascript', 'jsx' => 'const|let|function|return|if|else|async|await|import|from|export|default|new|try|catch|true|false|null',
            'bash', 'yaml', 'dockerfile', 'http', 'json', 'graphql', 'text' => 'FROM|RUN|COPY|WORKDIR|CMD|EXPOSE|ENV|query|mutation|type|GET|POST|PUT|PATCH|DELETE|true|false|null',
            default => 'SELECT|FROM|WHERE|FOR|UPDATE|INSERT|INTO|VALUES|SET|CREATE|TABLE|ALTER|ADD|CONSTRAINT|UNIQUE|PRIMARY|KEY|FOREIGN|REFERENCES|NOT|NULL|DEFAULT|CHECK|ENGINE|START|TRANSACTION|COMMIT|ROLLBACK|AND|OR|AS|CASE|WHEN|THEN|ELSE|END|GROUP|BY|ORDER|LIMIT|SUM|COUNT|BEGIN|DECIMAL|BIGINT|VARCHAR|TIMESTAMP|AUTO_INCREMENT|SIGNAL|SQLSTATE|IF|CALL|PROCEDURE|DECLARE|EXIT|HANDLER|RESIGNAL|DELIMITER',
        };
        // "--" starts a comment only in SQL; in shell code it is a flag (--queue). "#" comments shell, YAML and Dockerfiles.
        $comments = match ($language) {
            'sql' => '--[^\r\n]*|//[^\r\n]*|/\*[\s\S]*?\*/',
            'bash', 'yaml', 'dockerfile', 'python', 'py' => '(?<![\w:])#[^\r\n]*',
            default => '(?<!:)//[^\r\n]*|/\*[\s\S]*?\*/',
        };
        $pattern = '~(?<comment>'.$comments.')|(?<string>\'(?:\'\'|\\\\.|[^\'\\\\])*\'|"(?:\\\\.|[^"\\\\])*")|(?<keyword>\b(?:'.$keywords.')\b)|(?<number>\b\d+(?:\.\d+)?\b)~'.($language === 'sql' ? 'i' : '');
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

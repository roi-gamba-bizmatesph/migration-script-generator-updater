<?php
    /**
     * Migration script generator
     */
    
    $level          = '1';
    $rank           = 'A';
    $lessonStart    = '1';
    $lessonEnd      = '1';
    $lessonLocation = '';
    $lessonStartOrig= '1';
    $saveToDB       = 0;
    
    require_once 'vendor/autoload.php';

    //DB SETUP
    DB::$host     = '192.168.56.103';
    DB::$user     = 'bizmates';
    DB::$password = 'Bizmates2016!';
    DB::$dbName   = 'bizmates_new';
    DB::$encoding = 'utf8';

    if(isset($_POST['submit'])):


        class TinyHtmlMinifier
        {
            private $options;
            private $output;
            private $build;
            private $skip;
            private $skipName;
            private $head;
            private $elements;
        
            public function __construct(array $options)
            {
                $this->options = $options;
                $this->output = '';
                $this->build = [];
                $this->skip = 0;
                $this->skipName = '';
                $this->head = false;
                $this->elements = [
                    'skip' => [
                        'code',
                        'pre',
                        'script',
                        'textarea',
                    ],
                    'inline' => [
                        'a',
                        'abbr',
                        'acronym',
                        'b',
                        'bdo',
                        'big',
                        'br',
                        'cite',
                        'code',
                        'dfn',
                        'em',
                        'i',
                        'img',
                        'kbd',
                        'map',
                        'object',
                        'samp',
                        'small',
                        'span',
                        'strong',
                        'sub',
                        'sup',
                        'tt',
                        'var',
                        'q',
                    ],
                    'hard' => [
                        '!doctype',
                        'body',
                        'html',
                    ]
                ];
            }
        
            // Run minifier
            public function minify(string $html) : string
            {
                if (!isset($this->options['disable_comments']) ||
                    !$this->options['disable_comments']) {
                    $html = $this->removeComments($html);
                }
        
                $rest = $html;
        
                while (!empty($rest)) {
                    $parts = explode('<', $rest, 2);
                    $this->walk($parts[0]);
                    $rest = (isset($parts[1])) ? $parts[1] : '';
                }
        
                return $this->output;
            }
        
            // Walk trough html
            private function walk(&$part)
            {
                $tag_parts = explode('>', $part);
                $tag_content = $tag_parts[0];
        
                if (!empty($tag_content)) {
                    $name = $this->findName($tag_content);
                    $element = $this->toElement($tag_content, $part, $name);
                    $type = $this->toType($element);
        
                    if ($name == 'head') {
                        $this->head = $type === 'open';
                    }
        
                    $this->build[] = [
                        'name' => $name,
                        'content' => $element,
                        'type' => $type
                    ];
        
                    $this->setSkip($name, $type);
        
                    if (!empty($tag_content)) {
                        $content = (isset($tag_parts[1])) ? $tag_parts[1] : '';
                        if ($content !== '') {
                            $this->build[] = [
                                'content' => $this->compact($content, $name, $element),
                                'type' => 'content'
                            ];
                        }
                    }
        
                    $this->buildHtml();
                }
            }
        
            // Remove comments
            private function removeComments($content = '')
            {
                return preg_replace('/(?=<!--)([\s\S]*?)-->/', '', $content);
            }
        
            // Check if string contains string
            private function contains($needle, $haystack)
            {
                return strpos($haystack, $needle) !== false;
            }
        
            // Return type of element
            private function toType($element)
            {
                return (substr($element, 1, 1) == '/') ? 'close' : 'open';
            }
        
            // Create element
            private function toElement($element, $noll, $name)
            {
                $element = $this->stripWhitespace($element);
                $element = $this->addChevrons($element, $noll);
                $element = $this->removeSelfSlash($element);
                $element = $this->removeMeta($element, $name);
                return $element;
            }
        
            // Remove unneeded element meta
            private function removeMeta($element, $name)
            {
                if ($name == 'style') {
                    $element = str_replace(
                        [
                            ' type="text/css"',
                            "' type='text/css'"
                        ],
                        ['', ''],
                        $element
                    );
                } elseif ($name == 'script') {
                    $element = str_replace(
                        [
                            ' type="text/javascript"',
                            " type='text/javascript'"
                        ],
                        ['', ''],
                        $element
                    );
                }
                return $element;
            }
        
            // Strip whitespace from element
            private function stripWhitespace($element)
            {
                if ($this->skip == 0) {
                    $element = preg_replace('/\s+/', ' ', $element);
                }
                return trim($element);
            }
        
            // Add chevrons around element
            private function addChevrons($element, $noll)
            {
                if (empty($element)) {
                    return $element;
                }
                $char = ($this->contains('>', $noll)) ? '>' : '';
                $element = '<' . $element . $char;
                return $element;
            }
        
            // Remove unneeded self slash
            private function removeSelfSlash($element)
            {
                if (substr($element, -3) == ' />') {
                    $element = substr($element, 0, -3) . '>';
                }
                return $element;
            }
        
            // Compact content
            private function compact($content, $name, $element)
            {
                if ($this->skip != 0) {
                    $name = $this->skipName;
                } else {
                    $content = preg_replace('/\s+/', ' ', $content);
                }
        
                if (in_array($name, $this->elements['skip'])) {
                    return $content;
                } elseif (in_array($name, $this->elements['hard']) ||
                    $this->head) {
                    return $this->minifyHard($content);
                } else {
                    return $this->minifyKeepSpaces($content);
                }
            }
        
            // Build html
            private function buildHtml()
            {
                foreach ($this->build as $build) {
        
                    if (!empty($this->options['collapse_whitespace'])) {
        
                        if (strlen(trim($build['content'])) == 0)
                            continue;
        
                        elseif ($build['type'] != 'content' && !in_array($build['name'], $this->elements['inline']))
                            trim($build['content']);
        
                    }
        
                    $this->output .= $build['content'];
                }
        
                $this->build = [];
            }
        
            // Find name by part
            private function findName($part)
            {
                $name_cut = explode(" ", $part, 2)[0];
                $name_cut = explode(">", $name_cut, 2)[0];
                $name_cut = explode("\n", $name_cut, 2)[0];
                $name_cut = preg_replace('/\s+/', '', $name_cut);
                $name_cut = strtolower(str_replace('/', '', $name_cut));
                return $name_cut;
            }
        
            // Set skip if elements are blocked from minification
            private function setSkip($name, $type)
            {
                foreach ($this->elements['skip'] as $element) {
                    if ($element == $name && $this->skip == 0) {
                        $this->skipName = $name;
                    }
                }
                if (in_array($name, $this->elements['skip'])) {
                    if ($type == 'open') {
                        $this->skip++;
                    }
                    if ($type == 'close') {
                        $this->skip--;
                    }
                }
            }
        
            // Minify all, even spaces between elements
            private function minifyHard($element)
            {
                $element = preg_replace('!\s+!', ' ', $element);
                $element = trim($element);
                return trim($element);
            }
        
            // Strip but keep one space
            private function minifyKeepSpaces($element)
            {
                return preg_replace('!\s+!', ' ', $element);
            }
        }

        class TinyMinify
        {
            public static function html(string $html, array $options = []) : string
            {
                $minifier = new TinyHtmlMinifier($options);
                return $minifier->minify($html);
            }
        }

        function minify_css($input) 
        {
            if(trim($input) === "") return $input;
            return preg_replace(
                array(
                    // Remove comment(s)
                    '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')|\/\*(?!\!)(?>.*?\*\/)|^\s*|\s*$#s',
                    // Remove unused white-space(s)
                    '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\'|\/\*(?>.*?\*\/))|\s*+;\s*+(})\s*+|\s*+([*$~^|]?+=|[{};,>~]|\s(?![0-9\.])|!important\b)\s*+|([[(:])\s++|\s++([])])|\s++(:)\s*+(?!(?>[^{}"\']++|"(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')*+{)|^\s++|\s++\z|(\s)\s+#si',
                    // Replace `0(cm|em|ex|in|mm|pc|pt|px|vh|vw|%)` with `0`
                    '#(?<=[\s:])(0)(cm|em|ex|in|mm|pc|pt|px|vh|vw|%)#si',
                    // Replace `:0 0 0 0` with `:0`
                    '#:(0\s+0|0\s+0\s+0\s+0)(?=[;\}]|\!important)#i',
                    // Replace `background-position:0` with `background-position:0 0`
                    '#(background-position):0(?=[;\}])#si',
                    // Replace `0.6` with `.6`, but only when preceded by `:`, `,`, `-` or a white-space
                    '#(?<=[\s:,\-])0+\.(\d+)#s',
                    // Minify string value
                    '#(\/\*(?>.*?\*\/))|(?<!content\:)([\'"])([a-z_][a-z0-9\-_]*?)\2(?=[\s\{\}\];,])#si',
                    '#(\/\*(?>.*?\*\/))|(\burl\()([\'"])([^\s]+?)\3(\))#si',
                    // Minify HEX color code
                    '#(?<=[\s:,\-]\#)([a-f0-6]+)\1([a-f0-6]+)\2([a-f0-6]+)\3#i',
                    // Replace `(border|outline):none` with `(border|outline):0`
                    '#(?<=[\{;])(border|outline):none(?=[;\}\!])#',
                    // Remove empty selector(s)
                    '#(\/\*(?>.*?\*\/))|(^|[\{\}])(?:[^\s\{\}]+)\{\}#s'
                ),
                array(
                    '$1',
                    '$1$2$3$4$5$6$7',
                    '$1',
                    ':0',
                    '$1:0 0',
                    '.$1',
                    '$1$3',
                    '$1$2$4$5',
                    '$1$2$3',
                    '$1:0',
                    '$1$2'
                ),
            $input);
        }

        function clean_css($input)
        {
            // MAKE SURE SINGLE QUOTES ARE TURNED TO DOUBLE QUOTES
            $input = str_replace("'",'"',$input);
            $input = str_replace("/*# sourceMappingURL=main.css.map */", "", $input);
            return $input;
        }

        function unicode_escape_sequences($str)
        {
            $working = json_encode($str);
            $working = preg_replace('/\\\u([0-9a-z]{4})/', '&#x$1;', $working);
            return json_decode($working);
        }

        $level           = $_POST['level'];
        $rank            = $_POST['rank'];
        $lessonStart     = $_POST['from'];
        $lessonEnd       = $_POST['to'];
        $lessonLocation  = $_POST['directory'];
        $saveToDB        = $_POST['saveToDB'];
        $lessonStartOrig = $lessonStart;
        $updateQuery     = "";

        // RANKS BRACKETS
        $rankBrackets  = ['A'=>0,'B'=>20,'C'=>40,'D'=>60,'E'=>80];
        $levelBrackets = [1=>0,2=>100,3=>200,4=>300,5=>400,0=>500];

        $updateQuery .= "#Level {$level}-Rank $rank \n\n";

        //CREATES A FILE
        $outputfile = "output/Level{$level}{$rank} Lesson {$lessonStart} - {$lessonEnd}.sql";
        fopen($outputfile, "w");
        $fp = fopen($outputfile, 'a');//opens file in append mode 


        // GET RANK ID
        $ranks = [1=>'A',2=>'B',3=>'C',4=>'D',5=>'E'];
        $rankIDInc = 1;
        $levelInc = 1;
        $levelInc2 = 1;

        if($level == '0'):
            $levelConvert = 6;
        else:
            $levelConvert = $level;
        endif;

        for($levelInc; $levelInc<=$levelConvert; $levelInc++):
            $levelInc2 = 1;
            for($levelInc2; $levelInc2<=5;$levelInc2++):
                
                if($levelConvert == $levelInc && $ranks[$levelInc2] == $rank):
                    break;
                endif;

                $rankIDInc++;
            endfor;
        endfor;

        $rankId = $rankIDInc;

        for($lessonStart; $lessonStart<=$lessonEnd;$lessonStart++):
            $lessonSearcher = NULL;

            //PREPEND 0 TO NUMBER IF LESS THAN 10
            if($lessonStart < 10):
                $lessonSearcher = "0{$lessonStart}";
            else:
                $lessonSearcher = "{$lessonStart}";
            endif;

            // TEST GET LESSON FILES
            $htmlContents = file_get_contents("{$lessonLocation}\level{$level}-$rank\lesson-{$lessonSearcher}\index.html");
            $cssContents  = file_get_contents("{$lessonLocation}\level{$level}-$rank\lesson-{$lessonSearcher}\main.css");

            // MINIFY THE HTML AND CSS
            $minifiedHTML = TinyMinify::html($htmlContents);
            $cleanCss     = clean_css($cssContents);
            $minifiedCSS  = minify_css($cleanCss);

            $openBodyTagPosition = strpos($minifiedHTML, "<body>");

            // EXTRACT CONTENTS OF <body> TAG TO BE PROCESSED FOR ENCODING
            $bodyContentExtracted = substr($minifiedHTML, $openBodyTagPosition);
            $closeBodyTagPosition = strpos($bodyContentExtracted, "</body>");
            $bodyContentExtracted = substr($bodyContentExtracted, 0, $closeBodyTagPosition)."</body>";

            // $minifiedEncodedHTML = unicode_escape_sequences($bodyContentExtracted);
            $minifiedEncodedHTML = $bodyContentExtracted;
            $minifiedEncodedHTML = htmlspecialchars($bodyContentExtracted,ENT_QUOTES|ENT_HTML5, "UTF-8");


            // NEXT IS TO GET THE PROPER LESSON ID FOR EACH QUERY
            $lessonID = $rankBrackets[$rank] + $levelBrackets[$level] + $lessonStart;
            
            
            $updateQuery .= "#Lesson {$lessonSearcher}\n";
            $updateQuery .= "UPDATE mst_lesson_html SET content = '{$minifiedEncodedHTML}', css = '{$minifiedCSS}' where rank_id = {$rankId} AND lesson_id = {$lessonID};";
            $updateQuery .= "\n\n";
     
            if($saveToDB):
                $updateQueryForDB = "UPDATE mst_lesson_html SET content = '{$minifiedEncodedHTML}', css = '{$minifiedCSS}' where rank_id = {$rankId} AND lesson_id = {$lessonID};";
                DB::query($updateQueryForDB);
            endif;

        endfor;

        fwrite($fp, $updateQuery); 
        fclose($fp); 
    endif;

    
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migration Script Generator</title>
</head>
<body>
    
    <form action="#" method="POST">
        <input type="hidden" name="submit" value='true'>
        <label for="">Select Level:</label>
        <select name="level" id="">
            <option value="0" <?= $level == '0' ? 'selected' : ''; ?>>0</option>
            <option value="1" <?= $level == '1' ? 'selected' : ''; ?>>1</option>
            <option value="2" <?= $level == '2' ? 'selected' : ''; ?>>2</option>
            <option value="3" <?= $level == '3' ? 'selected' : ''; ?>>3</option>
            <option value="4" <?= $level == '4' ? 'selected' : ''; ?>>4</option>
            <option value="5" <?= $level == '5' ? 'selected' : ''; ?>>5</option>
        </select>
        <br/>
        <label for="">Select Rank:</label>
        <select name="rank" id="">
            <option value="A" <?= $rank == 'A' ? 'selected' : ''; ?>>A</option>
            <option value="B" <?= $rank == 'B' ? 'selected' : ''; ?>>B</option>
            <option value="C" <?= $rank == 'C' ? 'selected' : ''; ?>>C</option>
            <option value="D" <?= $rank == 'D' ? 'selected' : ''; ?>>D</option>
            <option value="E" <?= $rank == 'E' ? 'selected' : ''; ?>>E</option>
        </select>
        <br/>
        <label for="">Lesson (From - To):</label>
        <select name="from" id="">
            <?php
                $inc = 1;
                for($inc; $inc <= 20; $inc++):
                    ?>
                        <option value="<?= $inc; ?>" <?= $lessonStartOrig == $inc ? 'selected' : ''; ?>><?= $inc; ?></option>
                    <?php
                endfor;
            ?>
        </select>
        <select name="to" id="">
            <?php
                $inc = 1;
                for($inc; $inc <= 20; $inc++):
                    ?>
                        <option value="<?= $inc; ?>" <?= $lessonEnd == $inc ? 'selected' : ''; ?>><?= $inc; ?></option>
                    <?php
                endfor;
            ?>
        </select>
        <br/>
        <label for="">MyStage Directory:</label>
        <input type="text" name="directory" value="<?= isset($lessonLocation) ? $lessonLocation : ''?>">
        <i>ex. C:\Users\RoiMark.Gamba\Desktop\MyStage\mystage-pdf-to-html</i>
        <br/>
        <br/>
        <label>
            <input type="checkbox" class="form-check-input" name="saveToDB" value="1" <?= $saveToDB ? 'checked' : ''; ?>>
            Save to DB. <i>(After clicking submit, the generated MySQL script will automically be executed in the VM database)</i>
        </label>
        <br/>
        <br/>
        <input type="submit" value="Submit">
    </form>
</body>
</html>
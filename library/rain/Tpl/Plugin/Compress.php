<?php

namespace Rain\Tpl\Plugin;

require_once __DIR__ . '/../Plugin.php';

class Compress extends \Rain\Tpl\Plugin {

    protected $hooks = array('afterDraw'),
              $cache_dir, 
              $cache_path, 
              $base_url, 
              $conf;

    protected static $configure = array('html'      =>array('status'=>true),
                                        'css'       =>array('status'=>true),
                                        'javascript'=>array('status'=>false, 'position'=>'bottom'),
                                       );
    /**
     * Initialize the local configuration
     */
    public function __construct(){
        $this->conf = self::$configure;
    }
    
    /**
     * Function called in the hook afterDraw
     * @param \ArrayAccess $context 
     */
    public function afterDraw(\ArrayAccess $context) {

        // get the cache directory
        $this->cache_dir  = $context->conf['cache_dir'];
        $this->cache_path = $context->conf['cache_path'];
        $this->base_url   = $context->conf['base_url'];

        $html = $context->code;
        if( $this->conf['css']['status'] )
            $html = $this->compressCSS( $html );

        if( $this->conf['javascript']['status'] )
            $html = self::compressJavascript( $html );

        if( $this->conf['html']['status'] )
            $html = $this->compressHTML($html);

        // save the compressed code
        $context->code = $html;
    }

    /**
     * Compress the HTML
     * @param type $html
     * @return type 
     */
    protected function compressHTML($html) {

		$chunks = preg_split( '/(<pre.*?\/pre>)/ms', $html, -1, PREG_SPLIT_DELIM_CAPTURE );
		$html = '';
		$replace = array(
			'#[\n\r\t\s]+#'           => ' ',  // remove new lines & tabs
			'#>\s{2,}<#'              => '><', // remove inter-tag whitespace
			'#\/\*.*?\*\/#i'          => '',   // remove CSS & JS comments
			'#<!--(?![\[>]).*?-->#si' => '',   // strip comments, but leave IF IE (<!--[...]) and "<!-->""
			'#\s+<(html|head|meta|style|/style|title|script|/script|/body|/html|/ul|/ol|li)#' => '<$1', // before those elements, whitespace is dumb, so kick it out!!
			'#\s+(/?)>#' => '$1>', // just before the closing of " >"|" />"
			'#class="\s+#'=> 'class="', // at times, there is whitespace before class=" className"
			'#(script|style)>\s+#' => '$1>', // <script> var after_tag_has_whitespace = 'nonsens';
		);
		$search = array_keys($replace);
		foreach ( $chunks as $c )
		{
			if ( strpos( $c, '<pre' ) !== 0 )
			{
				$c = preg_replace($search, $replace, $c);
			}
			$html .= $c;
		}
		return $html;
    }



    /**
     * Compress the CSS
     * @param type $html
     * @return type 
     */
    protected function compressCSS($html) {

        // search for all stylesheet
        if (!preg_match_all("/<link.*href=\"(.*?\.css)\".*>/", $html, $matches))
            return $html; // return the HTML if doesn't find any

        // prepare the variables
        $externalUrl = array();
        $css = $cssName = "";
        $urlArray = array();

        $cssFiles = $matches[1];
        $md5Name = "";
        foreach( $cssFiles as $file ){
            $md5Name .= basename($file);
        }

        $cachedFilename = md5($md5Name);
        $cacheFolder 	= $this->cache_dir . "compress/css/"; // css cache folder
        $cachedFilepath = $cacheFolder . $cachedFilename . ".css";
		// clean link to tag slashes
		$cachedFilepath = preg_replace('/(\/+)/','/',$cachedFilepath);
        if( !file_exists($cachedFilepath) ){

            // read all the CSS found
            foreach ($cssFiles as $url) {

                // if a CSS is repeat it takes only the first
                if (empty($urlArray[$url])) {

                    $urlArray[$url] = 1;

                    // parse the URL
                    $parse = parse_url($url);

                    // read file
                    $stylesheetFile = file_get_contents($url);

                    // remove the comments
                    $stylesheetFile = preg_replace("!/\*[^*]*\*+([^/][^*]*\*+)*/!", "", $stylesheetFile);

                    // minify the CSS
                    $stylesheetFile = preg_replace("/\n|\r|\t|\s{4}/", "", $stylesheetFile);

                    $css .= "/*---\n CSS compressed in Tvss \n Compressor \n---*/\n\n" . $stylesheetFile . "\n";
                }
            }

            if (!is_dir($cacheFolder))
                mkdir($cacheFolder, 0755, $recursive = true);

            // save the stylesheet
            file_put_contents($cachedFilepath, $css);

        }

        // remove all the old stylesheet from the page
        $html = preg_replace("/<link.*href=\"(.*?\.css)\".*>/", "", $html);
		
        // create the tag for the stylesheet 
        $tag = '<link href="' . $this->base_url.$this->cache_path.'compress/css/'.$cachedFilename . ".css" . '" rel="stylesheet" type="text/css">';

        // add the tag to the end of the <head> tag
        $html = str_replace("</head>", $tag . "\n</head>", $html);

        // return the stylesheet
        return $html;
    }
    
    
    
    /**
     * Compress the CSS
     * @param type $html
     * @return type 
     */
    protected function compressJavascript($html) {

        $htmlToCheck = preg_replace("<!--.*?-->", "", $html);

        // search for javascript
        preg_match_all("/<script.*src=\"(.*?\.js)\".*>/", $htmlToCheck, $matches);
        $externalUrl = array();
        $javascript = "";

        $javascriptFiles = $matches[1];
        $md5Name = "";
        foreach( $javascriptFiles as $file ){
            $md5Name .= basename($file);
        }

        $cachedFilename = md5($md5Name);
        $cacheFolder = $this->cache_dir . "compress/js/"; // css cache folder
        $cachedFilepath = $cacheFolder . $cachedFilename . ".js";
        

        if( !file_exists($cachedFilepath) ){
            foreach ($matches[1] as $url) {

                // if a JS is repeat it takes only the first
                if (empty($urlArray[$url])) {
                    $urlArray[$url] = $url;

                    // reduce the path
                    //$url = \Rain\Tpl\Parser::reducePath( $url );

                    $javascriptFile = file_get_contents($url);

                    // minify the js
                    $javascriptFile = preg_replace("#/\*.*?\*/#", "", $javascriptFile);
                    $javascriptFile = preg_replace("#\n+|\t+| +#", " ", $javascriptFile);

                    $javascript .= "/*---\n Javascript compressed in Rain \n {$url} \n---*/\n\n" . $javascriptFile . "\n\n";
                    
                }
            }
            
            if (!is_dir($cacheFolder))
                mkdir($cacheFolder, 0755, $recursive = true);

            // save the stylesheet
            file_put_contents($cachedFilepath, $javascript);

        }

        $html = preg_replace("/<script.*src=\"(.*?\.js)\".*>/", "", $html);
        $tag = '<script src="' . $cachedFilepath . '"></script>';

        if( $this->conf['javascript']['position'] == 'bottom' ){
            $html = preg_replace("/<\/body>/", $tag . "</body>", $html);
        }
        else{
            $html = preg_replace("/<head>/", "<head>\n".$tag, $html);
        }

        return $html;
    }
    
    public function configure( $setting, $value ){
        $this->conf[$setting] = self::$configure[$setting] = $value;
    }

    public function configureLocal( $setting, $value ){
        $this->conf[$setting] = $value;
    }

}

<?php

class Rss {

  private $_channel = [];
  private $_channel_image = [];
  private $_items = [];

  public function __construct($title=NULL, $link=NULL, $description=NULL, $pubDate=NULL, $lastBuildDate=NULL, $otherElements=[], $imageElements=[]) {
    // necessary elements
    $this->_channel["title"] 		= $title ? $this->escape($title) : "";
    $this->_channel["link"] 		= $link ? $this->escape($link) : "";
    $this->_channel["description"] 	= $description ? $this->escape($description) : "";

    // special treatment for date
    $this->_channel["pubDate"] 		 = $pubDate;
    $this->_channel["lastBuildDate"] = $lastBuildDate;
	
    // other elements
    foreach ($otherElements as $tag => $value) {
      $this->_channel[$tag] = $value;
    }
	
	// other elements
    foreach ($imageElements as $tag => $value) {
      $this->_channel_image[$tag] = $value;
    }
	
  }
	
  public function set($tag, $value) {
    if (!in_array($tag, ["pubDate", "lastBuildDate"])) {
      $this->_channel[$tag] = $value;
    }
    else {
      // special treatment for date
      $this->_channel[$tag] = $value ? date(DATE_RSS,$value	) : date(DATE_RSS);
    }
  }

  public function addItem($elements = ["title" => "no title"]) { // specification demands at least a title/desc is present
    $this->_items[] = $elements;
  }
	
	public function generate() {
    $rss = <<<EOT
<?xml version="1.0" encoding="utf-8" ?>
<rss version="2.0">
  <channel>

EOT;
    foreach ($this->_channel as $tag => $val) {
      $rss .= "<".$tag.">";
      $rss .= $val;
      $rss .= "</".$tag.">";
      $rss .= PHP_EOL;
    }
	
	if(count($this->_channel_image)>0){
		$rss .= "<image>";
		foreach ($this->_channel_image as $tag => $val){			
			$rss .= "<".$tag.">";
			$rss .= $val;
			$rss .= "</".$tag.">";	
		}
		$rss .= "</image>";
		$rss .= PHP_EOL;
	}

    foreach ($this->_items as $item) {
      $rss .= "    <item>".PHP_EOL;
      foreach ($item as $tag => $val) {
		$rss .= "      <".$tag.">";
		$rss .= $val;
		$rss .= "</".$tag.">".PHP_EOL;
      }
      $rss .= "    </item>".PHP_EOL;
    }

    $rss .= <<<EOT
  </channel>
</rss>
EOT;
    return $rss;
  }
  
  public function publish() {
    ob_clean();
    header("Content-Type: text/xml; charset=utf-8"); 
    echo $this->generate();
  }

  private function escape($str) {
    return str_replace(
      array("\"","'","<",">","&"),
      array("&quot;","&apos;","&lt;","&gt;","&amp;"),
      $str
    );
  }
};
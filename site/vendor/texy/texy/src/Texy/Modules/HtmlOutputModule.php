<?php

/**
 * This file is part of the Texy! (https://texy.info)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Texy\Modules;

use Texy;
use Texy\HtmlElement;
use Texy\Regexp;


/**
 * HTML output
 */
final class HtmlOutputModule extends Texy\Module
{
	/** @var bool  indent HTML code? */
	public $indent = true;

	/** @var array */
	public $preserveSpaces = ['textarea', 'pre', 'script', 'code', 'samp', 'kbd'];

	/** @var int  base indent level */
	public $baseIndent = 0;

	/** @var int  wrap width, doesn't include indent space */
	public $lineWrap = 80;

	/** @deprecated */
	public $removeOptional = false;

	/** @var int  indent space counter */
	private $space = 0;

	/** @var array */
	private $tagUsed = [];

	/** @var array */
	private $tagStack = [];

	/** @var array  content DTD used, when context is not defined */
	private $baseDTD = [];


	public function __construct(Texy\Texy $texy)
	{
		$this->texy = $texy;
		$texy->addHandler('postProcess', [$this, 'postProcess']);
	}


	/**
	 * Converts <strong><em> ... </strong> ... </em>.
	 * into <strong><em> ... </em></strong><em> ... </em>
	 */
	public function postProcess(Texy\Texy $texy, string &$s): void
	{
		$this->space = $this->baseIndent;
		$this->tagStack = [];
		$this->tagUsed = [];

		// special "base content"
		$dtd = $texy->getDTD();
		$this->baseDTD = $dtd['div'][1] + $dtd['html'][1] /*+ $dtd['head'][1]*/ + $dtd['body'][1] + ['html' => 1];

		// wellform and reformat
		$s = Regexp::replace(
			$s . '</end/>',
			'#([^<]*+)<(?:(!--.*--)|(/?)([a-z][a-z0-9._:-]*)(|[ \n].*)\s*(/?))>()#Uis',
			[$this, 'cb']
		);

		// empty out stack
		foreach ($this->tagStack as $item) {
			$s .= $item['close'];
		}

		// right trim
		$s = Regexp::replace($s, "#[\t ]+(\n|\r|$)#", '$1'); // right trim

		// join double \r to single \n
		$s = str_replace("\r\r", "\n", $s);
		$s = strtr($s, "\r", "\n");

		// greedy chars
		$s = Regexp::replace($s, '#\x07 *#', '');
		// back-tabs
		$s = Regexp::replace($s, '#\t? *\x08#', '');

		// line wrap
		if ($this->lineWrap > 0) {
			$s = Regexp::replace(
				$s,
				'#^(\t*)(.*)$#m',
				[$this, 'wrap']
			);
		}
	}


	/**
	 * Callback function: <tag> | </tag> | ....
	 * @internal
	 */
	public function cb(array $matches): string
	{
		// html tag
		[, $mText, $mComment, $mEnd, $mTag, $mAttr, $mEmpty] = $matches;
		// [1] => text
		// [1] => !-- comment --
		// [2] => /
		// [3] => TAG
		// [4] => ... (attributes)
		// [5] => / (empty)

		$s = '';

		// phase #1 - stuff between tags
		if ($mText !== '') {
			$item = reset($this->tagStack);
			if ($item && !isset($item['dtdContent'][HtmlElement::INNER_TEXT])) {  // text not allowed?

			} elseif (array_intersect(array_keys($this->tagUsed, true), $this->preserveSpaces)) { // inside pre & textarea preserve spaces
				$s = Texy\Helpers::freezeSpaces($mText);

			} else {
				$s = Regexp::replace($mText, '#[ \n]+#', ' '); // otherwise shrink multiple spaces
			}
		}


		// phase #2 - HTML comment
		if ($mComment) {
			return $s . '<' . Texy\Helpers::freezeSpaces($mComment) . '>';
		}


		// phase #3 - HTML tag
		$mEmpty = $mEmpty || isset(HtmlElement::$emptyElements[$mTag]);
		if ($mEmpty && $mEnd) {
			return $s; // bad tag; /end/
		}


		if ($mEnd) { // end tag

			// has start tag?
			if (empty($this->tagUsed[$mTag])) {
				return $s;
			}

			// autoclose tags
			$tmp = [];
			$back = true;
			foreach ($this->tagStack as $i => $item) {
				$tag = $item['tag'];
				$s .= $item['close'];
				$this->space -= $item['indent'];
				$this->tagUsed[$tag]--;
				$back = $back && isset(HtmlElement::$inlineElements[$tag]);
				unset($this->tagStack[$i]);
				if ($tag === $mTag) {
					break;
				}
				array_unshift($tmp, $item);
			}

			if (!$back || !$tmp) {
				return $s;
			}

			// allowed-check (nejspis neni ani potreba)
			$item = reset($this->tagStack);
			$dtdContent = $item ? $item['dtdContent'] : $this->baseDTD;
			if (!isset($dtdContent[$tmp[0]['tag']])) {
				return $s;
			}

			// autoopen tags
			foreach ($tmp as $item) {
				$s .= $item['open'];
				$this->space += $item['indent'];
				$this->tagUsed[$item['tag']]++;
				array_unshift($this->tagStack, $item);
			}

		} else { // start tag

			$dtdContent = $this->baseDTD;

			$dtd = $this->texy->getDTD();
			if (!isset($dtd[$mTag])) {
				// unknown (non-html) tag
				$allowed = true;
				$item = reset($this->tagStack);
				if ($item) {
					$dtdContent = $item['dtdContent'];
				}

			} else {
				// optional end tag closing
				foreach ($this->tagStack as $i => $item) {
					// is tag allowed here?
					$dtdContent = $item['dtdContent'];
					if (isset($dtdContent[$mTag])) {
						break;
					}

					$tag = $item['tag'];

					// auto-close hidden, optional and inline tags
					if ($item['close'] && (!isset(HtmlElement::$optionalEnds[$tag]) && !isset(HtmlElement::$inlineElements[$tag]))) {
						break;
					}

					// close it
					$s .= $item['close'];
					$this->space -= $item['indent'];
					$this->tagUsed[$tag]--;
					unset($this->tagStack[$i]);
					$dtdContent = $this->baseDTD;
				}

				// is tag allowed in this content?
				$allowed = isset($dtdContent[$mTag]);

				// check deep element prohibitions
				if ($allowed && isset(HtmlElement::$prohibits[$mTag])) {
					foreach (HtmlElement::$prohibits[$mTag] as $pTag) {
						if (!empty($this->tagUsed[$pTag])) {
							$allowed = false;
							break;
						}
					}
				}
			}

			// empty elements se neukladaji do zasobniku
			if ($mEmpty) {
				if (!$allowed) {
					return $s;
				}

				$indent = $this->indent && !array_intersect(array_keys($this->tagUsed, true), $this->preserveSpaces);

				if ($indent && $mTag === 'br') { // formatting exception
					return rtrim($s) . '<' . $mTag . $mAttr . ">\n" . str_repeat("\t", max(0, $this->space - 1)) . "\x07";

				} elseif ($indent && !isset(HtmlElement::$inlineElements[$mTag])) {
					$space = "\r" . str_repeat("\t", $this->space);
					return $s . $space . '<' . $mTag . $mAttr . '>' . $space;

				} else {
					return $s . '<' . $mTag . $mAttr . '>';
				}
			}

			$open = null;
			$close = null;
			$indent = 0;

			/*
			if (!isset(Texy\HtmlElement::$inlineElements[$mTag])) {
				// block tags always decorate with \n
				$s .= "\n";
				$close = "\n";
			}
			*/

			if ($allowed) {
				$open = '<' . $mTag . $mAttr . '>';

				// receive new content
				if ($tagDTD = $dtd[$mTag] ?? null) {
					if (isset($tagDTD[1][HtmlElement::INNER_TRANSPARENT])) {
						$dtdContent += $tagDTD[1];
						unset($dtdContent[HtmlElement::INNER_TRANSPARENT]);
					} else {
						$dtdContent = $tagDTD[1];
					}
				}

				// format output
				if ($this->indent && !isset(HtmlElement::$inlineElements[$mTag])) {
					$close = "\x08" . '</' . $mTag . '>' . "\n" . str_repeat("\t", $this->space);
					$s .= "\n" . str_repeat("\t", $this->space++) . $open . "\x07";
					$indent = 1;
				} else {
					$close = '</' . $mTag . '>';
					$s .= $open;
				}

				// TODO: problematic formatting of select / options, object / params
			}


			// open tag, put to stack, increase counter
			$item = [
				'tag' => $mTag,
				'open' => $open,
				'close' => $close,
				'dtdContent' => $dtdContent,
				'indent' => $indent,
			];
			array_unshift($this->tagStack, $item);
			$tmp = &$this->tagUsed[$mTag];
			$tmp++;
		}

		return $s;
	}


	/**
	 * Callback function: wrap lines.
	 * @internal
	 */
	public function wrap(array $m): string
	{
		[, $space, $s] = $m;
		return $space . wordwrap($s, $this->lineWrap, "\n" . $space);
	}
}

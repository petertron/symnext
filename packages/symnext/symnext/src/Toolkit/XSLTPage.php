<?php

/**
 * @package Toolkit
 */

/**
 * XSLTPage extends the Page class to provide an object representation
 * of a Page that will be generated using XSLT.
 */

namespace Symnext\Toolkit;

use Symnext\Toolkit\XMLDocument;

class XSLTPage extends Page
{
    /**
     * An instance of the XSLTProcess class
     * @var XSLTProcess
     */
    public $Proc;

    /**
     * The XML to be transformed
     * @since Symphony 2.4 this variable may be a string or an XMLElement
     * @var string|XMLElement
     */
    protected $xmlDoc;

    /**
     * The XSL to apply to the `$this->xml`.
     * @var string|XMLElement
     */
    protected $xslDoc;

    protected $xslDir;

    /**
     * The constructor for the `XSLTPage` ensures that an `XSLTProcessor`
     * is available, and then sets an instance of it to `$this->Proc`, otherwise
     * it will throw a `SymphonyException` exception.
     */
    public function __construct(array $params = null)
    {
        parent::__construct();
        if (!XSLTProcess::isXSLTProcessorAvailable()) {
            #Symphony::Engine()->throwCustomError(__('No suitable XSLT processor was found.'));
            die('No suitable XSLT processor was found.');
        }

        $this->Proc = new XSLTProcess;
    }

    /**
     * Setter for `$this->xml`, can optionally load the XML from a file.
     *
     * @param string|XMLElement $xml
     *  The XML for this XSLT page
     * @param boolean $isFile
     *  If set to true, the XML will be loaded from a file. It is false by default
     */
    public function setXML(
        string|DOMDocument $xml
    ): void
    {
        if (is_string($xml)) {
            $doc = new DOMDocument($xml);
        }
        $this->xmlDoc = $xml;
    }

    /**
     * Accessor for the XML of this page
     *
     * @return string|XMLElement
     */
    public function getXML()
    {
        return $this->xmlDoc;
    }

    /**
     * Setter for `$this->stylesheet`, can optionally load the XSLT from a file.
     *
     * @param string $xsl
     *  The XSL for this XSLT page
     * @param boolean $isFile
     *  If set to true, the XSLT will be loaded from a file. It is false by default
     */
    public function setXSL(string|DOMDocument $xsl): void
    {
        $this->xslDoc = $xsl;
    }

    /**
     * Accessor for the XSL of this page
     *
     * @return string
     */
    public function getXSL(): string|XMLDocument
    {
        return $this->xslDoc;
    }

    /**
     * Returns an iterator of errors from the `XSLTProcess`. Use this function
     * inside a loop to get all the errors that occurring when transforming
     * `$this->xml` with `$this->stylesheet`.
     *
     * @return array
     *  An associative array containing the errors details from the
     *  `XSLTProcessor`
     */
    public function getError(): array
    {
        return $this->Proc->getError();
    }

    /**
     * The generate function calls on the `XSLTProcess` to transform the
     * XML with the given XSLT passing any parameters or functions
     * If no errors occur, the parent generate function is called to add
     * the page headers and a string containing the transformed result
     * is result.
     *
     * @param null $page
     * @return string
     */
    public function generate(array $view_data = null): string
    {
    #echo $this->xslDoc->saveXML(); die;
        $current_dir = getcwd();
        if (isset($this->xslDir)) {
            chdir($this->xslDir);
        }
        $result = $this->Proc->process($this->xmlDoc, $this->xslDoc);
        if (isset($this->xslDir)) {
            chdir($current_dir);
        }
        if ($this->Proc->isErrors()) {
            $this->setHttpStatus(Page::HTTP_STATUS_ERROR);
            return '';
        }

        //parent::generate();

        return $result;
    }
}

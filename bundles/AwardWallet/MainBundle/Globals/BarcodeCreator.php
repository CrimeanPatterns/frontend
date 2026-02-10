<?php

namespace AwardWallet\MainBundle\Globals;

class BarcodeCreator
{
    public $root;
    public $format;
    public $number;
    public $thickness = 30;

    /** @var \BCGDrawing */
    public $drawing;

    public function __construct($root)
    {
        $this->root = $root;

        require_once $this->root . '/lib/3dParty/barcodegen5/class/BCGFontFile.php';

        require_once $this->root . '/lib/3dParty/barcodegen5/class/BCGColor.php';

        require_once $this->root . '/lib/3dParty/barcodegen5/class/BCGDrawing.php';
    }

    public function setFormat($format)
    {
        $this->format = $format;
    }

    public function setNumber($number)
    {
        $this->number = $number;
    }

    public function setThickness($thickness)
    {
        $this->thickness = $thickness;
    }

    public function validate()
    {
        $code = $this->getCodeClass($this->format);
        $code->parse($this->number);
    }

    public function draw($scale = 1)
    {
        ob_start();
        $font = new \BCGFontFile(getSymfonyContainer()->getParameter("kernel.root_dir") . '/../data/fonts/arial.ttf', 18);
        $code = $this->getCodeClass($this->format);
        $code->setScale($scale); // Resolution
        $code->setThickness($this->thickness); // Thickness
        // The arguments are R, G, B for color.
        $color_black = new \BCGColor(0, 0, 0);
        $color_white = new \BCGColor(255, 255, 255);
        $code->setForegroundColor($color_black); // Color of bars
        $code->setBackgroundColor($color_white); // Color of spaces
        $code->setFont($font); // Font (or 0)
        $code->setLabel(null);
        $code->parse($this->number); // Text
        $drawing = new \BCGDrawing('', $color_white);
        $drawing->setBarcode($code);
        $drawing->draw();
        $this->drawing = $drawing;
        $drawing->finish(\BCGDrawing::IMG_FORMAT_PNG);

        return ob_get_clean();
    }

    public function getBinaryText()
    {
        $barCode = '';
        $image = $this->drawing->get_im();
        $width = imagesx($image);

        for ($n = 0; $n < $width; $n++) {
            $pixel = imagecolorat($image, $n, 0);

            if ($pixel == 0) {
                $barCode .= "1";
            } else {
                $barCode .= "0";
            }
        }

        return trim($barCode, '0');
    }

    protected function getCodeClass($format)
    {
        switch ($format) {
            case BAR_CODE_CODE_39:
                require_once $this->root . '/lib/3dParty/barcodegen5/class/BCGcode39.barcode.php';
                $code = new \BCGcode39();

                break;

            case BAR_CODE_UPC_A:
                require_once $this->root . '/lib/3dParty/barcodegen5/class/BCGupca.barcode.php';
                $code = new \BCGupca();

                break;

            case BAR_CODE_EAN_13:
                require_once $this->root . '/lib/3dParty/barcodegen5/class/BCGean13.barcode.php';
                $code = new \BCGean13();

                break;

            case BAR_CODE_CODE_128:
                require_once $this->root . '/lib/3dParty/barcodegen5/class/BCGcode128.barcode.php';
                $code = new \BCGcode128();

                break;

            case BAR_CODE_INTERLEAVED:
                require_once $this->root . '/lib/3dParty/barcodegen5/class/BCGi25.barcode.php';
                $code = new \BCGi25();

                break;

            case BAR_CODE_GS1_128:
                require_once $this->root . '/lib/3dParty/barcodegen5/class/BCGgs1128.barcode.php';
                $code = new \BCGgs1128();
                $code->setAllowsUnknownIdentifier(true);

                break;

            default:
                throw new \Exception("Unknown format: $format");
        }

        return $code;
    }
}

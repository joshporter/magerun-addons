<?php

namespace KJ\Magento\Util\ThemeComparison;

class LayoutItem extends \KJ\Magento\Util\AbstractUtil
{
    /**
     * @var \Symfony\Component\Finder\SplFileInfo
     */
    protected $_file;

    protected $_numberOfDifferences = 0;

    /** @var  \KJ\Magento\Util\ThemeComparison */
    protected $_comparison;

    /**
     * @param $file \Symfony\Component\Finder\SplFileInfo
     */
    public function __construct($file)
    {
        $this->_file = $file;
        return $this;
    }

    public function getFileName()
    {
        return 'layout/' . $this->_file->getRelativePathname();
    }

    public function setComparison($comparison)
    {
        $this->_comparison = $comparison;
    }

    public function matchPattern($pattern)
    {
        $haystack = $this->getFileName();

        if (strpos($pattern, '*') === false) {
            return ($haystack == $pattern);
        }

        $pattern = str_replace('*', '.*', $pattern);
        $pattern = str_replace('/', '\/', $pattern);
        $result = preg_match('/' . $pattern . '/', $haystack);

        return $result;
    }

    protected function _getAbsoluteFilePathToCompareAgainst()
    {
        // todokj The 'enterprise' needs to be determined dynamically
        $design = \Mage::getSingleton('core/design_package');
        $filename = $design->getLayoutFilename($this->_file->getRelativePathname(), array(
            '_area'    => 'frontend',
            '_package' => 'enterprise',
            '_theme'   => 'default',
        ));

        return $filename;
    }

    protected function _getAbsoluteFilePath()
    {
        $path = $this->_comparison->getMagentoInstanceRootDirectory()
            . '/app/design/frontend/' . $this->_comparison->getCurrentTheme()
            . '/' . $this->getFileName();

        return $path;
    }

    public function getDiff()
    {
        $fromFileFullPath = $this->_getAbsoluteFilePath();
        $toFileFullPath = $this->_getAbsoluteFilePathToCompareAgainst();
        if (!file_exists($toFileFullPath)) {
            return array("<info>Doesn't exist in base theme</info>");
        }

        $context = $this->_comparison->getLinesOfContext();
        $lines = $this->_executeShellCommand(sprintf('diff -U%s -w %s %s', $context, $fromFileFullPath, $toFileFullPath));

        foreach ($lines as & $line) {
            $comparisonItemLine = new \KJ\Magento\Util\Comparison\Item\Line($line);

            if ($comparisonItemLine->isAdditionLine()) {
                $line = "<info>" . $line . "</info>";
                $this->_numberOfDifferences++;
            }

            if ($comparisonItemLine->isRemovalLine()) {
                $line = "<comment>" . $line . "</comment>";
                $this->_numberOfDifferences++;
            }
        }

        return $lines;
    }
}
<?php declare(strict_types=1);

/**
 * Created by Roquie.
 * E-mail: roquie0@gmail.com
 * GitHub: Roquie
 * Date: 2019-01-17
 */

namespace StaticServer\Tests;

use StaticServer\Transfer;

class TransferTest extends TestCase
{
    public function testHowTransferObjectFillDefaultData()
    {
        $transfer = new Transfer('filename', 'realpath', 'js');

        $this->assertSame('filename', $transfer->getFilename());
        $this->assertSame('realpath', $transfer->getRealpath());
        $this->assertSame('js', $transfer->getExtension());
        $this->assertSame('', $transfer->getContent());
    }

    public function testHowSettersWorks()
    {
        $transfer = new Transfer('filename', 'realpath', 'js', 'content');
        $transfer->setFilename('f');
        $transfer->setRealpath('r');
        $transfer->setExtension('css');
        $transfer->setContent('file_content');

        $this->assertSame('f', $transfer->getFilename());
        $this->assertSame('r', $transfer->getRealpath());
        $this->assertSame('css', $transfer->getExtension());
        $this->assertSame('file_content', $transfer->getContent());
    }
}

<?php

namespace Doofinder\Feed\Test\Unit\Model\Generator\Component\Fetcher;

class ProductTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Doofinder\Feed\Model\Generator\Component\Fetcher\Product
     */
    private $_model;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    private $_productCollection;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    private $_productCollectionFactory;

    /**
     * @var \Doofinder\Feed\Model\Generator\ItemFactory
     */
    private $_generatorItemFactory;

    /**
     * @var \Magento\Catalog\Model\Product
     */
    private $_product;

    /**
     * @var \Doofinder\Feed\Model\Generator\Item
     */
    private $_item;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    private $_objectManagerHelper;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        $this->_objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->_product = $this->getMock(
            '\Magento\Catalog\Model\Product',
            ['getEntityId'],
            [],
            '',
            false
        );

        $this->_productCollection = $this->getMock(
            '\Magento\Catalog\Model\ResourceModel\Product\Collection',
            [
                'load',
                'addAttributeToSelect',
                'addStoreFilter',
                'setPageSize',
                'addAttributeToFilter',
                'addAttributeToSort',
                'getSize',
                'getLastItem',
            ],
            [],
            '',
            false
        );
        $this->_productCollection->expects($this->any())->method('addAttributeToSelect')
            ->willReturn($this->_productCollection);
        $this->_productCollection->expects($this->any())->method('addStoreFilter')
            ->willReturn($this->_productCollection);
        $this->_productCollection->expects($this->any())->method('addAttributeToSort')
            ->willReturn($this->_productCollection);
        $this->_productCollection->expects($this->any())->method('load')
            ->willReturn(array($this->_product));
        $this->_productCollection->expects($this->any())->method('getLastItem')
            ->willReturn($this->_product);

        $this->_productCollectionFactory = $this->getMock(
            '\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory',
            ['create'],
            [],
            '',
            false
        );
        $this->_productCollectionFactory->expects($this->any())->method('create')
            ->willReturn($this->_productCollection);

        $this->_item = $this->getMock(
            '\Doofinder\Feed\Model\Generator\Item',
            [],
            [],
            '',
            false
        );

        $this->_generatorItemFactory = $this->getMock(
            '\Doofinder\Feed\Model\Generator\ItemFactory',
            ['create'],
            [],
            '',
            false
        );
        $this->_generatorItemFactory->expects($this->any())->method('create')
            ->willReturn($this->_item);

        $this->_model = $this->_objectManagerHelper->getObject(
            '\Doofinder\Feed\Model\Generator\Component\Fetcher\Product',
            [
                'productCollectionFactory' => $this->_productCollectionFactory,
                'generatorItemFactory' => $this->_generatorItemFactory
            ]
        );
    }

    /**
     * Test fetch
     */
    public function testFetch()
    {
        $items = $this->_model->fetch();

        $this->assertEquals([$this->_item], $items);
        $this->assertEquals(true, $this->_model->isStarted());
        $this->assertEquals(true, $this->_model->isDone());
    }

    /**
     * Test fetch with pagination
     */
    public function testFetchWithPagination()
    {
        $this->_model->setLimit(1);
        $this->_model->setOffset(2);

        $this->_productCollection->expects($this->once())->method('setPageSize')
            ->with(1)
            ->willReturn(array($this->_product));
        $this->_productCollection->expects($this->once())->method('addAttributeToFilter')
            ->with('entity_id', ['gt' => 2])
            ->willReturn($this->_productCollection);

        $this->_model->fetch();
    }

    /**
     * Test isStarted() and isDone() methods
     *
     * @dataProvider testStartedDoneProvider
     */
    public function testStartedDone($offset, $size, $isStarted, $isDone)
    {
        $this->_productCollection->method('getSize')->willReturn($size);

        $this->_model->setLimit(1);
        $this->_model->setOffset($offset);

        $this->_model->fetch();

        $this->assertEquals($isStarted, $this->_model->isStarted());
        $this->assertEquals($isDone, $this->_model->isDone());
    }

    public function testStartedDoneProvider()
    {
        return [
            [0, 3, true, false],
            [1, 2, false, false],
            [2, 1, false, true],
        ];
    }

    /**
     * Test getLastProcessedEntityId() method
     */
    public function testGetLastProcessedEntityId()
    {
        $this->_product->method('getEntityId')->willReturn(11);
        $this->_productCollection->method('getSize')->willReturn(1);

        $this->_model->fetch();

        $this->assertEquals(11, $this->_model->getLastProcessedEntityId());
    }
}

<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\BonusReferral\Ui\Component\Listing\Column;

/**
 * Add
 */
class Actions
    extends \Magento\Ui\Component\Listing\Columns\Column
{
    const A_SALE_REF = \Praxigento\BonusReferral\Ui\DataProvider\Grid\Bonus\Referral\Referral::A_SALE_REF;
    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;


    public function __construct(
        \Magento\Framework\View\Element\UiComponent\ContextInterface $context,
        \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory,
        \Magento\Framework\UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    )
    {
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $name = $this->getData('name');
            foreach ($dataSource['data']['items'] as & $item) {
                $item[$name]['edit'] = [
                    'label' => __('Edit'),
                    'id' => $item[self::A_SALE_REF]
                ];
            }
        }

        return $dataSource;
    }
}
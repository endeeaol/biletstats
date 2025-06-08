<?php
/**
 * 2007-2025 PrestaShop
 * ... (Nagłówek licencji bez zmian) ...
 */

class AdminBiletStatsController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->display = 'view';
        parent::__construct();

        if (!$this->module || !$this->module->active) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminDashboard'));
        }
        $this->page_header_toolbar_title = $this->l('Statystyki Sprzedaży Biletów');
    }

    public function renderView()
    {
        /** @var Biletstats $module */
        $module = $this->module;

        // --- OBSŁUGA SORTOWANIA DLA DRUGIEJ TABELI ---
        $defaultSortBy = 'id_order';
        $defaultSortOrder = 'DESC';
        $sortBy = Tools::getValue('biletstatsSortBy', $defaultSortBy);
        $sortOrder = Tools::getValue('biletstatsSortOrder', $defaultSortOrder);
        $validSortColumns = ['id_order', 'date_add', 'status_name', 'id_customer', 'customer_name', 'email', 'ticket_quantity'];
        if (!in_array($sortBy, $validSortColumns)) {
            $sortBy = $defaultSortBy;
        }
        if (strtoupper($sortOrder) !== 'ASC' && strtoupper($sortOrder) !== 'DESC') {
            $sortOrder = $defaultSortOrder;
        }

        // --- PRZYGOTOWANIE DANYCH DLA PIERWSZEJ, PODSUMOWUJĄCEJ TABELI ---
        $statsData = [
            [
                'label' => $this->l('Wszystkie bilety sprzedane (bez anulowanych)'),
                'value' => $module->getSoldTicketsTotal(),
            ],
            [
                'label' => $this->l('- w tym już opłacone (bez anulowanych)'),
                'value' => $module->getSoldTicketsPaid(),
            ],
            [
                'label' => $this->l('- w tym czekające na Weronikę aż skończy pazy (przelew niepotwierdzony)'),
                'value' => $module->getAwaitingBankWire(),
            ],
            [
                'label' => $this->l('- w tym bilety co zaczął płacić przez paynow i usnął'),
                'value' => $module->getPaynowUnfinished(),
            ],
            [
                'label' => $this->l('- w tym bilety u Omnipaków łoboziaków'),
                'value' => $module->getWithOmnipak(),
            ],
            [
                'label' => $this->l('- w tym bilety mknące kurierem'),
                'value' => $module->getWithCourier(),
            ],
            [
                'label' => $this->l('Bilety anulowane'),
                'value' => $module->getCancelledTicketsNew(),
            ],
        ];

        // Pobranie danych do drugiej, szczegółowej tabeli
        $detailedOrders = $module->getDetailedOrdersInfo($sortBy, $sortOrder);

        // --- PRZYPISANIE WSZYSTKICH DANYCH DO SZABLONU SMARTY ---
        $this->context->smarty->assign([
            'statsData' => $statsData,
            'detailedOrdersData' => $detailedOrders,
            'biletstats_icon' => $this->module->getPathUri() . 'logo.png',
            'productIdBilet' => Biletstats::PRODUCT_ID_BILET,
            'dateFromOrdersBilet' => substr(Biletstats::DATE_FROM_ORDERS_BILET, 0, 10),
            // Przekazanie stałych do szablonu, aby wyjaśnić logikę w stopce
            'statusGroupAllSold' => implode(', ', Biletstats::STATUS_GROUP_ALL_SOLD_HISTORY),
            'statusGroupPaid' => implode(', ', Biletstats::STATUS_GROUP_PAID_HISTORY),
            'statusCancelled' => Biletstats::STATUS_CANCELLED,
            'statusAwaiting' => implode(', ', Biletstats::STATUS_AWAITING_BANK_WIRE),
            'statusPaynow' => Biletstats::STATUS_PAYNOW_UNFINISHED,
            'statusOmnipak' => Biletstats::STATUS_OMNIPAK,
            'statusCourier' => Biletstats::STATUS_WITH_COURIER,
            // Przekazanie aktualnych parametrów sortowania do szablonu
            'currentSortBy' => $sortBy,
            'currentSortOrder' => strtoupper($sortOrder),
            'currentControllerUrl' => $this->context->link->getAdminLink('AdminBiletStats'),
        ]);

        return $this->context->smarty->fetch($this->getTemplatePath() . 'bilet_stats_view.tpl');
    }

    public function postProcess()
    {
        parent::postProcess();
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);
        $this->context->smarty->assign('page_title', $this->l('Statystyki Biletów'));
    }
}
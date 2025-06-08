<?php
/**
 * 2007-2025 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2025 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Biletstats extends Module
{
    // --- Główne ustawienia ---
    public const PRODUCT_ID_BILET = 78;
    public const DATE_FROM_ORDERS_BILET = '2025-06-05 00:00:00';

    // --- Grupy statusów dla nowych obliczeń ---
    public const STATUS_GROUP_ALL_SOLD_HISTORY = [1, 2, 3, 4, 5, 9, 10, 12, 13, 17, 27, 28];
    public const STATUS_GROUP_PAID_HISTORY = [1, 2, 3, 4, 5, 9, 13, 27, 28];
    public const STATUS_AWAITING_BANK_WIRE = [10, 12];
    public const STATUS_PAYNOW_UNFINISHED = 17;
    public const STATUS_OMNIPAK = 27;
    public const STATUS_WITH_COURIER = 5;
    public const STATUS_CANCELLED = 6;

    public function __construct()
    {
        $this->name = 'biletstats';
        $this->tab = 'analytics_stats';
        $this->version = '1.0.7';
        $this->author = 'AI Assistant (przykład)';
        $this->need_instance = 1;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Statystyki Biletów');
        $this->description = $this->l('Wyświetla zaawansowane statystyki sprzedaży dla produktu-biletu o ID 78 oraz sortowalną listę zamówień.');
        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => _PS_VERSION_];
    }

    public function install(): bool
    {
        if (!parent::install()) {
            return false;
        }
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminBiletStats';
        $tab->name = [];
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = $this->l('Statystyki Biletów (Strona)', false, $lang['iso_code']);
        }
        $parentTabId = (int)Tab::getIdFromClassName('AdminParentStats');
        if (!$parentTabId) {
            $parentTabId = (int)Tab::getIdFromClassName('IMPROVE');
        }
        $tab->id_parent = $parentTabId;
        $tab->module = $this->name;
        return $tab->add();
    }

    public function uninstall(): bool
    {
        $id_tab = (int)Tab::getIdFromClassName('AdminBiletStats');
        if ($id_tab) {
            $tab = new Tab($id_tab);
            if (Validate::isLoadedObject($tab)) {
                $tab->delete();
            }
        }
        return parent::uninstall();
    }

    // --- METODY OBLICZENIOWE DLA TABELI PODSUMOWANIA ---

    public function getSoldTicketsTotal(): int
    {
        $status_list = implode(',', array_map('intval', self::STATUS_GROUP_ALL_SOLD_HISTORY));
        $sql = new DbQuery();
        $sql->select('SUM(od.product_quantity)');
        $sql->from('orders', 'o');
        $sql->innerJoin('order_detail', 'od', 'o.id_order = od.id_order');
        $sql->where('od.product_id = ' . (int)self::PRODUCT_ID_BILET);
        $sql->where('o.date_add >= \'' . pSQL(self::DATE_FROM_ORDERS_BILET) . '\'');
        $sql->where('EXISTS (SELECT 1 FROM `' . _DB_PREFIX_ . 'order_history` oh WHERE oh.id_order = o.id_order AND oh.id_order_state IN (' . $status_list . '))');
        $sql->where('o.current_state != ' . (int)self::STATUS_CANCELLED);

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
        return $result ? (int)$result : 0;
    }

    public function getSoldTicketsPaid(): int
    {
        $status_list = implode(',', array_map('intval', self::STATUS_GROUP_PAID_HISTORY));
        $sql = new DbQuery();
        $sql->select('SUM(od.product_quantity)');
        $sql->from('orders', 'o');
        $sql->innerJoin('order_detail', 'od', 'o.id_order = od.id_order');
        $sql->where('od.product_id = ' . (int)self::PRODUCT_ID_BILET);
        $sql->where('o.date_add >= \'' . pSQL(self::DATE_FROM_ORDERS_BILET) . '\'');
        $sql->where('EXISTS (SELECT 1 FROM `' . _DB_PREFIX_ . 'order_history` oh WHERE oh.id_order = o.id_order AND oh.id_order_state IN (' . $status_list . '))');
        
        $excluded_statuses = array_merge(self::STATUS_AWAITING_BANK_WIRE, [self::STATUS_CANCELLED]);
        $excluded_list = implode(',', array_map('intval', $excluded_statuses));
        $sql->where('o.current_state NOT IN (' . $excluded_list . ')');

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
        return $result ? (int)$result : 0;
    }

    private function getTicketsByCurrentStatus(int|array $statusIds): int
    {
        $sql = new DbQuery();
        $sql->select('SUM(od.product_quantity)');
        $sql->from('orders', 'o');
        $sql->innerJoin('order_detail', 'od', 'o.id_order = od.id_order');
        $sql->where('od.product_id = ' . (int)self::PRODUCT_ID_BILET);
        $sql->where('o.date_add >= \'' . pSQL(self::DATE_FROM_ORDERS_BILET) . '\'');

        if (is_array($statusIds)) {
            $status_list = implode(',', array_map('intval', $statusIds));
            if (empty($status_list)) {
                return 0;
            }
            $sql->where('o.current_state IN (' . $status_list . ')');
        } else {
            $sql->where('o.current_state = ' . (int)$statusIds);
        }

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
        return $result ? (int)$result : 0;
    }

    public function getAwaitingBankWire(): int
    {
        return $this->getTicketsByCurrentStatus(self::STATUS_AWAITING_BANK_WIRE);
    }

    public function getPaynowUnfinished(): int
    {
        return $this->getTicketsByCurrentStatus(self::STATUS_PAYNOW_UNFINISHED);
    }

    public function getWithOmnipak(): int
    {
        return $this->getTicketsByCurrentStatus(self::STATUS_OMNIPAK);
    }

    public function getWithCourier(): int
    {
        return $this->getTicketsByCurrentStatus(self::STATUS_WITH_COURIER);
    }

    public function getCancelledTicketsNew(): int
    {
        return $this->getTicketsByCurrentStatus(self::STATUS_CANCELLED);
    }
    
    // --- METODA DLA DRUGIEJ, SZCZEGÓŁOWEJ TABELI ---
    
    public function getDetailedOrdersInfo(string $sortBy = 'id_order', string $sortOrder = 'DESC'): array
    {
        $id_lang_pl = (int)Language::getIdByIso('pl');
        if (!$id_lang_pl) {
            $id_lang_pl = (int)Context::getContext()->language->id;
        }

        $columnMap = [
            'id_order' => 'o.id_order',
            'date_add' => 'o.date_add',
            'status_name' => 'osl.name',
            'id_customer' => 'o.id_customer',
            'customer_name' => 'c.lastname',
            'email' => 'c.email',
            'ticket_quantity' => 'ticket_quantity_alias',
        ];
        
        $orderByColumn = $columnMap[$sortBy] ?? 'o.id_order';
        $orderWay = (strtoupper($sortOrder) === 'ASC') ? 'ASC' : 'DESC';

        $sql = new DbQuery();
        $sql->select('o.id_order, o.date_add, o.id_customer');
        $sql->select('osl.name AS status_name');
        $sql->select('c.firstname, c.lastname, c.email');
        $sql->select('(SELECT SUM(od_sum.product_quantity) 
                       FROM `'._DB_PREFIX_.'order_detail` od_sum 
                       WHERE od_sum.id_order = o.id_order 
                       AND od_sum.product_id = '.(int)self::PRODUCT_ID_BILET.') AS ticket_quantity_alias');
        $sql->from('orders', 'o');
        $sql->innerJoin('customer', 'c', 'o.id_customer = c.id_customer');
        $sql->innerJoin('order_state_lang', 'osl', 'o.current_state = osl.id_order_state AND osl.id_lang = '.$id_lang_pl);
        $sql->where('EXISTS (SELECT 1 FROM `'._DB_PREFIX_.'order_detail` od_check 
                             WHERE od_check.id_order = o.id_order 
                             AND od_check.product_id = '.(int)self::PRODUCT_ID_BILET.')');
        $sql->where('o.date_add >= \'' . pSQL(self::DATE_FROM_ORDERS_BILET) . '\'');
        $sql->orderBy(pSQL($orderByColumn) . ' ' . pSQL($orderWay));

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
        return $result ? $result : [];
    }
}
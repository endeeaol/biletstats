<style>
div.biletstat table.table{
	max-width:700px !important;
}
div.biletstat table.table td{
	font-size: 14px;
	padding: 10px;
}
</style>


{* Moduł BiletStats - Widok Statystyk *}



<div class="panel biletstat">
    <div class="panel-heading">
        <i class="icon-ticket"></i>
        To ile my nasprzedawali tych biletów, Aga?
    </div>

    {if isset($statsData) && $statsData}
		<h4><strong>Wartość wiersza pierwszego musi się zgadzać z sumą wierszy: 2 + 3 + 4</strong></h4>
		 
        <table class="table table-striped">
            {* ... (sekcja thead i tbody bez zmian, iteruje po $statsData) ... *}
            <thead>
                <tr>
                    <th>{l s='Opis' mod='biletstats'}</th>
                    <th class="text-right">{l s='Wartość' mod='biletstats'}</th>
                </tr>
            </thead>
            <tbody>
                {foreach from=$statsData item=stat}
                    <tr>
                        <td>{$stat.label|escape:'htmlall':'UTF-8'}</td>
                        <td class="text-center"><strong>{$stat.value|intval}</strong></td>
                    </tr>
                {/foreach}
            </tbody>
        </table>
		
		
    {else}
        <div class="alert alert-info">
            {l s='Brak danych statystycznych do wyświetlenia dla wybranego okresu.' mod='biletstats'}
        </div>
    {/if}

    {* ZAKTUALIZOWANA STOPKA DLA PIERWSZEJ TABELI *}
    <div class="panel-footer">
		
        <p>
            <i class="icon-info-circle"></i> {l s='Informacje dotyczące raportu statystyk:' mod='biletstats'}
        </p>
        <ul>
            <li>{l s='ID produktu (biletu) analizowanego w tym raporcie:' mod='biletstats'} <strong>{$productIdBilet|escape:'htmlall':'UTF-8'}</strong></li>
            <li>{l s='Wszystkie dane w tym raporcie są liczone od daty:' mod='biletstats'} <strong>{$dateFromOrdersBilet|escape:'htmlall':'UTF-8'}</strong></li>
            <li>
                {l s='"Wszystkie sprzedane" obejmują zamówienia z historią statusów (%s) i których aktualny status nie jest Anulowany (ID %d).' sprintf=[$statusGroupAllSold, $statusCancelled] mod='biletstats'}
            </li>
            <li>
                {l s='"Wszystkie opłacone" obejmują zamówienia z historią statusów (%s) i których aktualny status nie jest Anulowany (ID %d) ani Oczekujący na przelew (ID %d).' sprintf=[$statusGroupPaid, $statusCancelled, $statusAwaiting] mod='biletstats'}
            </li>
            <li>{l s='Pozostałe wiersze bazują wyłącznie na aktualnym statusie zamówienia:' mod='biletstats'}
                <ul>
                    <li>{l s='Oczekujące na przelew: ID %d' sprintf=[$statusAwaiting] mod='biletstats'}</li>
					<li>{l s='Niedokończona płatność Paynow: ID %d' sprintf=[$statusPaynow] mod='biletstats'}</li>
                    <li>{l s='U Omnipaków: ID %d' sprintf=[$statusOmnipak] mod='biletstats'}</li>
                    <li>{l s='U kuriera: ID %d' sprintf=[$statusCourier] mod='biletstats'}</li>
                    <li>{l s='Anulowane: ID %d' sprintf=[$statusCancelled] mod='biletstats'}</li>
                </ul>
            </li>
        </ul>
    </div>
</div>
 

{* NOWA TABELA ZE SZCZEGÓŁOWYMI ZAMÓWIENIAMI - Z SORTOWANIEM *}
<div class="panel">
    <div class="panel-heading">
        <i class="icon-list-alt"></i>
        {l s='Szczegółowa lista zamówień z biletami (jest pozycji mniej niż biletów, bo niektórzy mają po dwa) od %date%' sprintf=['%id%' => $productIdBilet, '%date%' => $dateFromOrdersBilet] mod='biletstats'}
    </div>

    {if isset($detailedOrdersData) && $detailedOrdersData|@count > 0}
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead>
                    <tr>
                        <th>{l s='L.p.' mod='biletstats'}</th>
                        <th>
                            <a href="{$currentControllerUrl|escape:'htmlall':'UTF-8'}&amp;biletstatsSortBy=id_order&amp;biletstatsSortOrder={if $currentSortBy eq 'id_order' and $currentSortOrder eq 'ASC'}DESC{else}ASC{/if}">
                                {l s='Id. zam.' mod='biletstats'}
                                {if $currentSortBy eq 'id_order'}
                                    <i class="icon-caret-{if $currentSortOrder eq 'ASC'}up{else}down{/if}"></i>
                                {/if}
                            </a>
                        </th>
                        <th>
                            <a href="{$currentControllerUrl|escape:'htmlall':'UTF-8'}&amp;biletstatsSortBy=date_add&amp;biletstatsSortOrder={if $currentSortBy eq 'date_add' and $currentSortOrder eq 'ASC'}DESC{else}ASC{/if}">
                                {l s='Data' mod='biletstats'}
                                {if $currentSortBy eq 'date_add'}
                                    <i class="icon-caret-{if $currentSortOrder eq 'ASC'}up{else}down{/if}"></i>
                                {/if}
                            </a>
                        </th>
                        <th>
                             <a href="{$currentControllerUrl|escape:'htmlall':'UTF-8'}&amp;biletstatsSortBy=status_name&amp;biletstatsSortOrder={if $currentSortBy eq 'status_name' and $currentSortOrder eq 'ASC'}DESC{else}ASC{/if}">
                                {l s='Status' mod='biletstats'}
                                {if $currentSortBy eq 'status_name'}
                                    <i class="icon-caret-{if $currentSortOrder eq 'ASC'}up{else}down{/if}"></i>
                                {/if}
                            </a>
                        </th>
                        <th>
                            <a href="{$currentControllerUrl|escape:'htmlall':'UTF-8'}&amp;biletstatsSortBy=id_customer&amp;biletstatsSortOrder={if $currentSortBy eq 'id_customer' and $currentSortOrder eq 'ASC'}DESC{else}ASC{/if}">
                                {l s='Id. klienta' mod='biletstats'}
                                {if $currentSortBy eq 'id_customer'}
                                    <i class="icon-caret-{if $currentSortOrder eq 'ASC'}up{else}down{/if}"></i>
                                {/if}
                            </a>
                        </th>
                        <th>
                             <a href="{$currentControllerUrl|escape:'htmlall':'UTF-8'}&amp;biletstatsSortBy=customer_name&amp;biletstatsSortOrder={if $currentSortBy eq 'customer_name' and $currentSortOrder eq 'ASC'}DESC{else}ASC{/if}">
                                {l s='Imię i Nazwisko' mod='biletstats'}
                                {if $currentSortBy eq 'customer_name'}
                                    <i class="icon-caret-{if $currentSortOrder eq 'ASC'}up{else}down{/if}"></i>
                                {/if}
                            </a>
                        </th>
                        <th>
                            <a href="{$currentControllerUrl|escape:'htmlall':'UTF-8'}&amp;biletstatsSortBy=email&amp;biletstatsSortOrder={if $currentSortBy eq 'email' and $currentSortOrder eq 'ASC'}DESC{else}ASC{/if}">
                                {l s='Email' mod='biletstats'}
                                {if $currentSortBy eq 'email'}
                                    <i class="icon-caret-{if $currentSortOrder eq 'ASC'}up{else}down{/if}"></i>
                                {/if}
                            </a>
                        </th>
                        <th class="text-right">
                            <a href="{$currentControllerUrl|escape:'htmlall':'UTF-8'}&amp;biletstatsSortBy=ticket_quantity&amp;biletstatsSortOrder={if $currentSortBy eq 'ticket_quantity' and $currentSortOrder eq 'ASC'}DESC{else}ASC{/if}">
                                {l s='Biletów sztuk' mod='biletstats'}
                                {if $currentSortBy eq 'ticket_quantity'}
                                    <i class="icon-caret-{if $currentSortOrder eq 'ASC'}up{else}down{/if}"></i>
                                {/if}
                            </a>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$detailedOrdersData item=order name=orderLoop}
                        <tr>
                            <td>{$smarty.foreach.orderLoop.iteration}</td>
                            <td>{$order.id_order|intval}</td>
                            <td>{$order.date_add|date_format:"%Y-%m-%d %H:%M"}</td>
                            <td>{$order.status_name|escape:'htmlall':'UTF-8'}</td>
                            <td>{$order.id_customer|intval}</td>
                            <td>{$order.firstname|escape:'htmlall':'UTF-8'} {$order.lastname|escape:'htmlall':'UTF-8'}</td>
                            <td>{$order.email|escape:'htmlall':'UTF-8'}</td>
                            <td class="text-right">{$order.ticket_quantity_alias|intval}</td> {* Użycie aliasu z SQL *}
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    {else}
        <div class="alert alert-info">
            {l s='Brak szczegółowych zamówień z produktem ID %id% od daty %date% do wyświetlenia.' sprintf=['%id%' => $productIdBilet, '%date%' => $dateFromOrdersBilet] mod='biletstats'}
        </div>
    {/if}
</div>
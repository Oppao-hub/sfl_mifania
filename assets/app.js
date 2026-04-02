import './bootstrap';
import './styles/app.css';

// 1. Force Load jQuery using 'require' (Prevents hoisting)
const $ = require('jquery');

// 2. Immediately make it global (Critical for DataTables to see it)
window.$ = window.jQuery = $;

// 3. Force Load DataTables & Extensions
require('datatables.net-dt');
require('datatables.net-responsive-dt');
require('datatables.net-responsive-dt/css/responsive.dataTables.css');

// 4. Initialize Function
const initializeDashboardTables = () => {
    const selector = '.js-datatable';

    $(selector).each(function() {
        const $table = $(this);

        // Now $.fn.DataTable should be defined safely
        if ($.fn.DataTable && $.fn.DataTable.isDataTable(this)) {
            $table.DataTable().destroy();
        }

        $table.DataTable({
            // Your Dashboard-Themed Layout
            dom: '<"dt-controls flex flex-row flex-wrap items-center justify-between mb-6 gap-4" <"flex items-center gap-4"lf> i>rt<"flex justify-end items-center mt-6"p>',
            language: {
                search: "",
                searchPlaceholder: "Search records...",
                lengthMenu: "_MENU_",
                info: "<span class='text-[10px] font-black uppercase tracking-widest text-brand bg-brand/10 px-3 py-1.5 rounded-lg border border-brand/20'>Total: _TOTAL_ Records</span>",
                infoEmpty: "<span class='text-[10px] font-black uppercase tracking-widest text-gray'>0 Records</span>",
                infoFiltered: "<span class='text-[9px] text-gray ml-2'>(Filtered)</span>",
            },
            pagingType: "simple_numbers",
            pageLength: 10,
            autoWidth: false,
            responsive: false,
            retrieve: false,
        });
    });
};

// 5. Run Logic
$(function() {
    initializeDashboardTables();
});

document.addEventListener("turbo:load", initializeDashboardTables);
document.addEventListener("turbo:render", initializeDashboardTables);

document.addEventListener("turbo:before-cache", () => {
    $('.js-datatable').each(function() {
        if ($.fn.DataTable && $.fn.DataTable.isDataTable(this)) {
            // Destroys the table and restores the original clean HTML
            $(this).DataTable().destroy();
        }
    });
});

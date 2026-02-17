import './bootstrap';
import './styles/app.css';

// 1. Force Load jQuery using 'require' (Prevents hoisting)
const $ = require('jquery');

// 2. Immediately make it global (Critical for DataTables to see it)
window.$ = window.jQuery = $;

// 3. Force Load DataTables (It will now see the global window.$)
require('datatables.net-dt');

// 4. Initialize Function
const initializeDashboardTables = () => {
    const selector = '.js-datatable';

    $(selector).each(function() {
        const $table = $(this);

        // Now $.fn.DataTable should be defined
        if ($.fn.DataTable && $.fn.DataTable.isDataTable(this)) {
            $table.DataTable().destroy();
        }

        $table.DataTable({
            // Your Dashboard-Themed Layout(DataTables)
            dom: '<"dt-controls flex flex-row items-center justify-between mb-6 ml-1 mt-2 gap-4" <"flex items-center gap-3"lf> i>rt<"flex justify-end items-center mt-6"p>',
            language: {
                search: "",
                searchPlaceholder: "Search...",
                lengthMenu: "_MENU_",
                info: "Total: _TOTAL_ Records",
                infoEmpty: "0 Records",
                infoFiltered: "(Filtered)",
            },
            pagingType: "simple_numbers",
            pageLength: 10,
            autoWidth: false,
            responsive: true,
            retrieve: false
        });
    });
};

// 5. Run Logic
$(function() {
    initializeDashboardTables();
});

document.addEventListener("turbo:load", initializeDashboardTables);
document.addEventListener("turbo:render", initializeDashboardTables);

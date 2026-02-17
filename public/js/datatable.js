// 1. You MUST import these inside this file so specific variables work
import 'datatables.net-buttons-dt'; // Import Buttons extension
import 'datatables.net-buttons/js/buttons.html5.mjs'; // HTML5 export buttons
import 'datatables.net-buttons/js/buttons.print.mjs'; // Print button
import DataTable from 'datatables.net-dt';
import 'datatables.net-dt/css/jquery.dataTables.css';
import 'datatables.net-responsive-dt'; // Import responsive extension
import 'datatables.net-responsive-dt/css/responsive.dataTables.css'; // Import responsive CSS
import $ from 'jquery';

// 2. Link DataTables to jQuery
DataTable(window, $);

const initializeDashboardTables = () => {
    console.log('Initializing Dashboard Tables...')
    const selector = '.js-datatable';

    $(selector).each(function() {
        // 3. Prevent duplicate wrappers by destroying the old instance
        if ($.fn.DataTable.isDataTable(this)) {
            $(this).DataTable().destroy();
            // REMOVED $(this).empty(); - This was deleting your data!
        }

        // 4. Initialize
        $(this).DataTable({
            // Your custom layout
            dom: '<"flex flex-wrap justify-between items-center gap-4 mb-4"lf>t<"flex justify-between items-center mt-4"ip>',
            language: {
                search: "",
                searchPlaceholder: "Search...",
                lengthMenu: "_MENU_ Per page",
            },
            pagingType: "simple_numbers",
            lengthMenu: [[5, 10, 15, 20], [5, 10, 15, 20]],
            lengthChange: true,
            autoWidth: false,
            responsive: true, // Ensure responsive is enabled
            retrieve: false // Set to false because we handled the destroy manually above
        });
    });
};

// 5. Initialize on Standard Load
$(function() {
    initializeDashboardTables();
});

// 6. Initialize on Turbo Navigation (Reload/Link Click)
document.addEventListener("turbo:load", initializeDashboardTables);

// 7. Fix for "Back Button" issues where Turbo restores a broken cache
document.addEventListener("turbo:render", initializeDashboardTables);

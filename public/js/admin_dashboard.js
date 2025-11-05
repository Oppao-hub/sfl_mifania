$("#admin-table").DataTable({
    dom: '<"flex flex-wrap justify-between items-center gap-4 mb-4"lf>t<"flex justify-between items-center mt-4"ip>',
    language: {
        search: "",
        searchPlaceholder: "Search...",
        lengthMenu: "_MENU_ Per page",
    },
    pagingType: "simple_numbers",
    lengthMenu: [
        [5, 10, 15, 20],
        [5, 10, 15, 20],
    ],
    lengthChange: true,
});

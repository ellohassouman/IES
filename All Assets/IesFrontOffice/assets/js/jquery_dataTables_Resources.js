var culture = getCookie('_culture');

var en = {
    "sProcessing": "Processing...",
    "sLengthMenu": "Display _MENU_ records per page",
    "sZeroRecords": "No results",
    "sEmptyTable": "<span class='data-table-empty-text'>No data available at this table</span>",
    "sInfo": "Showing _START_ to _END_ of _TOTAL_ entries",
    "sInfoEmpty": "Showing 0 to 0 of 0 records",
    "sInfoFiltered": "(filtered from _MAX_ total records)",
    "sSearch": "Search:",
    "sInfoThousands": " ",
    "sLoadingRecords": "Loading...",
    "oPaginate": { "sNext": "Next", "sPrevious": "Previous", "sFirst": "<<", "sLast": ">>" }
};

var fr = {
    "sProcessing": "Transformation...",
    "sLengthMenu": "Afficher les enregistrements de _MENU_",
    "sZeroRecords": "Aucun résultat trouvé",
    "sEmptyTable": "<span class='data-table-empty-text'>Pas de données disponibles à ce tableau</span>",
    "sInfo": "Afficher _START_ à _END_ sur _TOTAL_ éléments",
    "sInfoEmpty": "Afficher 0 à 0 sur 0 éléments",
    "sInfoFiltered": "(dossiers de filtrage totalisant _MAX_)",
    "sInfoPostFix": "",
    "sSearch": "Recherche:",
    "sInfoThousands": ",",
    "sLoadingRecords": "Chargement...",
    "oPaginate": { "sNext": "Suivant", "sPrevious": "Précédent", "sFirst": "<<", "sLast": ">>" }
};

var currentLang = (culture != null && culture == 'fr') ? fr : en;
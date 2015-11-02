$j(document).ready(function () {
    $j('#LogfileCsvexport').click(function () {
        $j.fileDownload('../' + mtvpath + 'multitv.connector.php', {
            httpMethod: "POST",
            data: {
                mtvpath: mtvpath,
                mode: 'dbtable',
                config: csvexportConfig,
                configtype: 'module',
                action: 'csvexport'
            },
            successCallback: function () {
                alert('CSV-Datei erfolgreich erzeugt!');
            },
            failCallback: function () {
                alert('Es ist ein Fehler beim Erzeugen der CSV-Datei aufgetreten!');
            }
        });
    });
});

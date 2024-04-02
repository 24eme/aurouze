// https://stackoverflow.com/a/45036446

db.getCollectionNames().forEach(function(col) {
    var indexes = db[col].getIndexes();
    indexes.forEach(function (c) {
        var fields = '', result = '', options = {};
        for (var i in c) {
            if (i == 'key') {
                fields = c[i];
            } else if (i == 'name' && c[i] == '_id_') {
                return;
            } else if (i != 'name' && i != 'v' && i != 'ns') {
                options[i] = c[i];
            }
        }
        var fields  = JSON.stringify(fields);
            options = JSON.stringify(options);
        if (options == '{}') {
            result = "db." + col + ".createIndex(" + fields + "); ";
        } else {
            result = "db." + col + ".createIndex(" + fields + ", " + options + "); ";
        }
        result = result
            .replace(/{"floatApprox":-1,"top":-1,"bottom":-1}/ig, '-1')
            .replace(/{"floatApprox":(-?\d+)}/ig, '$1')
            .replace(/\{"\$numberLong":"(-?\d+)"\}/ig, '$1');
        print(result);
    });
});

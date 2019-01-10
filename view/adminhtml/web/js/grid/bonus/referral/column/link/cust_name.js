define([
    "Praxigento_Core/js/grid/column/link"
], function (Column) {
    "use strict";

    return Column.extend({
        defaults: {
            idAttrName: "custId",
            route: "/customer/index/edit/id/"
        }
    });
});

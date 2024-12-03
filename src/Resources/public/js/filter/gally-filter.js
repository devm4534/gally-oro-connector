define(function(require) {
    'use strict';

    const $ = require('jquery');
    const routing = require('routing');
    const MultiSelectFilter = require('oro/filter/multiselect-filter');
    const LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    const __ = require('orotranslation/js/translator');

    /**
     * Gally select filter: filter values as multiple select options and
     * add a view more button if all the options are not displayed
     *
     * @export  oro/filter/gally-filter
     * @class   oro.filter.GallyFilter
     * @extends oro.filter.MultiSelectFilter
     */
    const GallyFilter = MultiSelectFilter.extend({

        custom_data: {},

        /**
         * @inheritdoc
         */
        initialize: function (options) {
            GallyFilter.__super__.initialize.call(this, options);
            const viewMoreLabel = __('gally.filter.showMore.label');
            this.showMoreLink = $('<a/>', {href: '#', html: viewMoreLabel, click: this.showMore.bind(this)});
        },

        /**
         * @inheritdoc
         */
        render: function() {
            GallyFilter.__super__.render.call(this);

            if (this.custom_data.hasMore) {
                this.selectWidget.multiselect('open');
                this.selectWidget.getWidget().append(this.showMoreLink);
                this.selectWidget.multiselect('close');
            }
            if (!this.subview('loading')) {
                this.subview('loading', new LoadingMaskView({container: this.$el}));
            }
        },

        showMore: function() {
            this.subview('loading').show();
            let urlParams = Object.fromEntries(new URLSearchParams(window.location.search));
            urlParams['field'] = this.name;

            $.ajax({
                url: routing.generate('gally_filter_view_more', urlParams),
                method: 'GET',
                success: function (response) {
                    this.custom_data.hasMore = false;
                    this.showMoreLink.hide();
                    this._setChoices(response);
                    this.render();
                    this.subview('loading').hide();
                }.bind(this),
                error: function (xhr) {
                    this.subview('loading').hide();
                }.bind(this),
            });
        }
    });

    return GallyFilter;
});

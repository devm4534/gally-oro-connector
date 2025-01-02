define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
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
        },

        /**
         * @inheritdoc
         */
        render: function() {
            GallyFilter.__super__.render.call(this);

            // Defers the view more link rendering to be sure it is displayed after the filter options.
            setTimeout(function () {
                if (!this.showMoreLink) {
                    const viewMoreLabel = __('gally.filter.showMore.label');
                    this.showMoreLink = $('<a/>', {href: '#', html: viewMoreLabel, click: this.showMore.bind(this)});
                    this.selectWidget.multiselect('open');
                    this.selectWidget.getWidget().append(this.showMoreLink);
                    if (!this.subview('loading')) {
                        this.subview('loading', new LoadingMaskView({container: this.selectWidget.getWidget()}));
                    }
                    this.selectWidget.multiselect('close');
                    this.showMoreLink.hide();
                }

                if (this.custom_data.hasMore) {
                    this.showMoreLink.show();
                } else {
                    this.showMoreLink.hide();
                }

            }.bind(this), 100);

        },

        onMetadataLoaded: function(metadata) {
            this.custom_data = metadata.custom_data;
            this.choices = metadata.choices;
            GallyFilter.__super__.onMetadataLoaded.call(this, metadata);
        },

        filterTemplateData: function(data) {
            if (this.counts === null) {
                return data;
            } else if (_.isEmpty(this.counts)) {
                this.counts = Object.create(null);
            }

            let options = $.extend(true, {}, this.choices || {});
            const filterOptions = option => {
                if (this.isDisableFiltersEnabled && _.has(this.countsWithoutFilters, option.value)) {
                    option.disabled = true;
                } else {
                    options = _.without(options, option);
                }
            };

            _.each(options, option => {
                this.counts[option.value] = option.count;
                // option.count = this.counts[option.value] || 0;
                option.disabled = false;
                if (option.count === 0 &&
                    !_.contains(data.selected.value, option.value)
                ) {
                    filterOptions(option);
                }
            });

            const nonZeroOptions = _.filter(options, option => {
                return option.count > 0;
            });
            if (nonZeroOptions.length === 1) {
                _.each(options, option => {
                    if (option.count === this.totalRecordsCount &&
                        !_.contains(data.selected.value, option.value)
                    ) {
                        filterOptions(option);
                    }
                });
            }

            this.visible = !_.isEmpty(options);
            data.options = options;

            return data;
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

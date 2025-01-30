# Gally Plugin for Oro Commerce

## Installation

Install this bundle with composer:

```shell
composer require gally/oro-plugin:dev-master
```

## Usage

- In oro backend, you can enable gally and add credentials from configuration screen.
- Run this command to sync your catalog structure with Gally :
    ```shell
        bin/console gally:structure-sync # Sync catalog and source field data with gally
    ```
- Run a full index from Oro to Gally.
    ```shell
        bin/console oro:website-search:reindex # Index category and product entity to gally
    ```
- At this step, you should be able to see your product and source field in the Gally backend.
- They should also appear in your Oro frontend when searching or browsing categories.
- And you're done !


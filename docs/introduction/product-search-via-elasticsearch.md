# Product Searching via Elasticsearch
To provide the best possible performance, frontend product searching and autocomplete
leverages [Elasticsearch technology](https://www.elastic.co/products/elasticsearch).

## Product data export
Following product attributes are exported into Elasticsearch (i.e. the search is performed on these fields only):
* name
* catnum
* partno
* ean
* description
* short description

Data of all products are exported into Elasticsearch by CRON module (`ElasticsearchExportCronModule`) once an hour.
Alternatively, you can force the export manually using `elasticsearch-products-export` phing target.

## Searching for products

We use the same method for searching and for autocomplete, so results are always the same.

Understanding elasticsearch searching is difficult.
But if we simplify, we can say that the search term is searched in attributes and is prioritized in following order:
* ean - exact match
* name - match any of words
* name - match any of words ignoring diacritics
* catnum - exact match
* name - match any of words in root form
* partno - exact match
* name - match in first couple of letters of any word
* name - match in first couple of letters of any word ignoring diacritics
* ean - match in first couple of letters
* catnum - match in first couple of letters
* partno - match in first couple of letters
* short description - match anywhere
* description - match anywhere

The searched fields and their priority are defined directly in the `ElasticsearchSearchClient::createQuery()` function.

## Elasticsearch index setting
The Elasticsearch indexes are created during application build.
You can also create or delete indexes manually using phing targets `elasticsearch-indexes-create`, and `elasticsearch-indexes-delete` respectively,
or you can use `elasticsearch-indexes-recreate` that encapsulates the previous two.

Unique index is created for each domain as each domain has distinct locale.
To discover the exact mapping setting, you can look at the JSON configuration files
that are located in `src/Resources/elasticsearch/` directory in [`shopsys/framework`](https://github.com/shopsys/framework) package.
The directory is configured using `%shopsys.framework.elasticsearch_sources_dir%` parameter.

## Where does Elasticsearch run?
When using docker installation, Elasticsearch API is available on the address [http://127.0.0.1:9200](http://127.0.0.1:9200).

## How to change the default index, data export setting, and searching behaviour?
If you wish to reconfigure the indexes setting, simply change the `%shopsys.framework.elasticsearch_sources_dir%` parameter
to your custom directory and put your own JSON configurations in it using the same naming pattern (`<domain_id>.json`).

If you need to change the data that are exported into Elasticsearch, overwrite appropriate methods in `ElasticsearchProductRepository` and `ElasticsearchProductTranslator` classes.

You can also change the searching behavior by overwriting `ElasticsearchSearchClient` class.
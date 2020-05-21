<?php

namespace WPGraphQLGutenberg\Schema\Types\InterfaceType;

use GraphQL\Deferred;
use WPGraphQLGutenberg\Schema\Utils;
use WPGraphQLGutenberg\Blocks\PostMeta;
use WPGraphQLGutenberg\PostTypes\BlockEditorPreview;

class BlockEditorContentNode {
	private $type_registry;

	function __construct() {
		$fields = [
			'blocks' => [
				'type' => [
					'list_of' => ['non_null' => 'Block']
				],
				'description' => __('Gutenberg blocks', 'wp-graphql-gutenberg'),
				'resolve' => function ($model, $args, $context, $info) {
					$loader = $context->loaders['blocks'];
					$id = $model->ID;
					$loader->add($id);

					return new Deferred(function () use (&$loader, $id) {
						$loader->load();
						return $loader->get($id);
					});
				}
			],
			'blocksJSON' => [
				'type' => 'String',
				'description' => __('Gutenberg blocks as json string', 'wp-graphql-gutenberg'),
				'resolve' => function ($model, $args, $context, $info) {
					$loader = $context->loaders['blocks'];
					$id = $model->ID;
					$loader->add($id);

					return new Deferred(function () use (&$loader, $id) {
						$loader->load();
						return json_encode($loader->get($id));
					});
				}
			],
			'previewBlocks' => [
				'type' => [
					'list_of' => ['non_null' => 'Block']
				],
				'description' => __('Previewed gutenberg blocks', 'wp-graphql-gutenberg'),
				'resolve' => Utils::ensure_capability(
					function ($model) {
						$id = BlockEditorPreview::get_preview_id($model->ID, $model->ID);

						if (!empty($id)) {
							return PostMeta::get_post($id)['blocks'];
						}

						return null;
					},
					function ($cap) {
						return $cap->edit_posts;
					}
				)
			],
			'previewBlocksJSON' => [
				'type' => 'String',
				'description' => __('Previewed Gutenberg blocks as json string', 'wp-graphql-gutenberg'),
				'resolve' => Utils::ensure_capability(
					function ($model) {
						$id = BlockEditorPreview::get_preview_id($model->ID, $model->ID);

						if (!empty($id)) {
							return json_encode(PostMeta::get_post($id)['blocks']);
						}

						return null;
					},
					function ($cap) {
						return $cap->edit_posts;
					}
				)
			]
		];

		add_action('graphql_register_types', function ($type_registry) use ($fields) {
			$this->type_registry = $type_registry;

			register_graphql_interface_type('BlockEditorContentNode', [
				'description' => __('Gutenberg post interface', 'wp-graphql-gutenberg'),
				'fields' => $fields,
				'resolveType' => function ($model) use ($type_registry) {
					return $type_registry->get_type(Utils::get_post_graphql_type($model, $type_registry));
				}
			]);

			$types = Utils::get_editor_graphql_types();

			if (count($types)) {
				register_graphql_interfaces_to_types(['BlockEditorContentNode'], $types);
			}
		});
	}
}

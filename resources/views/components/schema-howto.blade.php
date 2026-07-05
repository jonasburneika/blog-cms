@props(['post'])
@php $schema = \BlogCms\Support\SchemaBuilder::howTo($post); @endphp
{!! $schema?->toScript() !!}

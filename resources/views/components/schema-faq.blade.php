@props(['post'])
@php $schema = \BlogCms\Support\SchemaBuilder::faqPage($post); @endphp
{!! $schema?->toScript() !!}

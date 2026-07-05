@props(['post'])
@php $schema = \BlogCms\Support\SchemaBuilder::article($post); @endphp
{!! $schema?->toScript() !!}

@extends('errors.layout')

@section('code', '403')
@section('title', __('Access denied'))
@section('message', $exception?->getMessage() ?: __('You do not have permission to access this page.'))

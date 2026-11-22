@extends('layouts.app')
@section('title', 'Sign in')
@section('content')
    <h1>Sign in</h1>
    <form method="post" action="{{ route('login') }}" class="card">
        @csrf
        <label for="name">Name</label>
        <input id="name" name="name" value="{{ old('name') }}"
            autocomplete="off" required>
        @error('name')
            <p class="error">{{ $message }}</p>
        @enderror
        <label for="password">Password</label>
        <input id="password" name="password" type="password" required>
        <button type="submit">Sign in</button>
    </form>
@endsection

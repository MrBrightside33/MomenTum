@props(['w' => '100%', 'h' => '4rem', 'rounded' => '0.75rem'])

<span {{ $attributes->class(['sk block']) }} style="width: {{ $w }}; height: {{ $h }}; border-radius: {{ $rounded }};" aria-hidden="true"></span>

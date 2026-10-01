<x-atrium::form.input name="display_name" :label="__('roster::roster.display_name')" :value="old('display_name', $profile?->display_name)" />
<x-atrium::form.input name="avatar_url" type="url" :label="__('roster::roster.avatar_url')" :value="old('avatar_url', $profile?->avatar_url)" />
<x-atrium::form.input name="timezone" :label="__('roster::roster.timezone')" :value="old('timezone', $profile?->timezone)" />
<x-atrium::form.input name="locale" :label="__('roster::roster.locale')" :value="old('locale', $profile?->locale)" />
<x-atrium::form.textarea name="bio" :label="__('roster::roster.bio')" :value="old('bio', $profile?->bio)" />

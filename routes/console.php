<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('store:cleanup')->daily();

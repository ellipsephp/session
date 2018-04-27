<?php

use Ellipse\Session;

describe('Session', function () {

    beforeEach(function () {

        $this->data = [
            'key1' => 'value1',
            'key2' => 'value2',
            Session::FLASH => [
                'key3' => 'value3',
            ],
        ];

        $this->session = new Session($this->data);

    });

    it('should unset the flash namespace of the data array ref', function () {

        expect($this->data)->not->toContainKey(Session::FLASH);

    });

    describe('->id()', function () {

        it('should return the current session id', function () {

            session_id('session_id');

            $test = $this->session->id();

            expect($test)->toEqual('session_id');

        });

    });

    describe('->regenerate_id()', function () {

        beforeEach(function () {

            $this->test = null;

            allow('session_regenerate_id')->toBeCalled()->andRun(function ($flag) {

                $this->test = $flag;

            });

        });

        context('when called with false as parameter', function () {

            it('should proxy session_regenerate_id() with false as parameter', function () {

                $this->session->regenerate_id();

                expect($this->test)->not->toBeNull();
                expect($this->test)->toBeFalsy();

            });

        });

        context('when called with true as parameter', function () {

            it('should proxy session_regenerate_id() with true as parameter', function () {

                $this->session->regenerate_id(true);

                expect($this->test)->not->toBeNull();
                expect($this->test)->toBeTruthy();

            });

        });

    });

    describe('all', function () {

        context('when the flashed data array is empty', function () {

            it('should return all the current and previous data array', function () {

                $test = $this->session->all();

                expect($test)->toEqual([
                    'key1' => 'value1',
                    'key2' => 'value2',
                    'key3' => 'value3',
                ]);

            });

        });

        context('when the flashed data array is not empty', function () {

            it('should return all the current, flashed and previous data array', function () {

                $this->session->flash('key4', 'value4');

                $test = $this->session->all();

                expect($test)->toEqual([
                    'key1' => 'value1',
                    'key2' => 'value2',
                    'key3' => 'value3',
                    'key4' => 'value4',
                ]);

            });

        });

        context('when a key is both in current and previous data array', function () {

            it('should return the current value for this key', function () {

                $this->session->set('key3', 'new_value3');

                $test = $this->session->all();

                expect($test)->toEqual([
                    'key1' => 'value1',
                    'key2' => 'value2',
                    'key3' => 'new_value3',
                ]);

            });

        });

        context('when a key is both in flashed and previous data array', function () {

            it('should return the flashed value for this key', function () {

                $this->session->flash('key3', 'new_value3');

                $test = $this->session->all();

                expect($test)->toEqual([
                    'key1' => 'value1',
                    'key2' => 'value2',
                    'key3' => 'new_value3',
                ]);

            });

        });

    });

    describe('->has()', function () {

        context('when the given key is present in the current data array', function () {

            it('should return true', function () {

                $test = $this->session->has('key1');

                expect($test)->toBeTruthy();

            });

        });

        context('when the given key is in the previous data array', function () {

            it('should return true', function () {

                $test = $this->session->has('key3');

                expect($test)->toBeTruthy();

            });

        });

        context('when the given key is in the flashed data array', function () {

            it('should return true', function () {

                $this->session->flash('key4', 'value4');

                $test = $this->session->has('key4');

                expect($test)->toBeTruthy();

            });

        });

        context('when the given key is not present in the current, previous or flashed data array', function () {

            it('should return false', function () {

                $test = $this->session->has('notset');

                expect($test)->toBeFalsy();

            });

        });

    });

    describe('->get()', function () {

        context('when the given key is present in the current data array', function () {

            it('should return the value associated with the given key', function () {

                $test = $this->session->get('key1');

                expect($test)->toEqual('value1');

            });

        });

        context('when the given key is present in the previous data array', function () {

            it('should return the value associated with the given key', function () {

                $test = $this->session->get('key3');

                expect($test)->toEqual('value3');

            });

        });

        context('when the given key is present in the flashed data array', function () {

            it('should return the value associated with the given key', function () {

                $this->session->flash('key4', 'value4');

                $test = $this->session->get('key4');

                expect($test)->toEqual('value4');

            });

        });

        context('when the given key is present both in the current and previous data array', function () {

            it('should return the current value associated with the given key', function () {

                $this->session->set('key3', 'new_value3');

                $test = $this->session->get('key3');

                expect($test)->toEqual('new_value3');

            });

        });

        context('when the given key is present both in the flashed and previous data array', function () {

            it('should return the flashed value associated with the given key', function () {

                $this->session->flash('key3', 'new_value3');

                $test = $this->session->get('key3');

                expect($test)->toEqual('new_value3');

            });

        });

        context('when the given key is not present in the current, previous or flashed data array', function () {

            context('when a default value is given', function () {

                it('should return the given default value', function () {

                    $test = $this->session->get('notset', 'default');

                    expect($test)->toEqual('default');

                });

            });

            context('when no default value is given', function () {

                it('should return null', function () {

                    $test = $this->session->get('notset');

                    expect($test)->toBeNull();

                });

            });

        });

    });

    describe('->set()', function () {

        it('should associate the given key with the given value', function () {

            $this->session->set('key4', 'value4');

            $test = $this->session->get('key4');

            expect($test)->toEqual('value4');

        });

        it('should update the data array ref', function () {

            $this->session->set('key4', 'value4');

            $test = $this->data['key4'];

            expect($test)->toEqual('value4');

        });

        it('should overvrite keys with the same name in the flashed data array', function () {

            $this->session->flash('key4', 'flashed');
            $this->session->set('key4', 'value4');

            expect($this->data['key4'])->toEqual('value4');
            expect($this->data[Session::FLASH])->not->toContainKey('key4');

        });

    });

    describe('->flash()', function () {

        it('should associate the given key with the given value', function () {

            $this->session->flash('key4', 'value4');

            $test = $this->session->get('key4');

            expect($test)->toEqual('value4');

        });

        it('should update the data array ref', function () {

            $this->session->flash('key4', 'value4');

            $test = $this->data[Session::FLASH]['key4'];

            expect($test)->toEqual('value4');

        });

        it('should overvrite keys with the same name in the current data array', function () {

            $this->session->set('key4', 'current');
            $this->session->flash('key4', 'value4');

            expect($this->data[Session::FLASH]['key4'])->toEqual('value4');
            expect($this->data)->not->toContainKey('key4');

        });

    });

    describe('->unset()', function () {

        context('when the key is in the current data array', function () {

            it('should unset the given key', function () {

                $this->session->unset('key1');

                $test = $this->session->has('key1');

                expect($test)->toBeFalsy();

            });

            it('should update the data array ref', function () {

                $this->session->unset('key1');

                expect($this->data)->not->toContainKey('key1');

            });

        });

        context('when the key is in the previous data array', function () {

            it('should unset the given key', function () {

                $this->session->unset('key3');

                $test = $this->session->has('key3');

                expect($test)->toBeFalsy();

            });

        });

        context('when the key is in the flashed data array', function () {

            it('should unset the given key', function () {

                $this->session->flash('key4', 'session4');

                $this->session->unset('key4');

                $test = $this->session->has('key4');

                expect($test)->toBeFalsy();

            });

        });

    });

    describe('->delete()', function () {

        it('should unset all the keys', function () {

            $this->session->flash('key4', 'value4');

            $this->session->delete();

            $test = $this->session->all();

            expect($this->data)->toEqual([]);

        });

        it('should update the data array ref', function () {

            $this->session->delete();

            expect($this->data)->toEqual([]);

        });

    });

});

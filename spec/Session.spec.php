<?php

use Ellipse\Session;

describe('Session', function () {

    beforeEach(function () {

        $this->data = ['key1' => 'value1'];

        $this->session = new Session($this->data);

    });

    describe('->has()', function () {

        context('when the given key is present in the data array', function () {

            it('should return true', function () {

                $test = $this->session->has('key1');

                expect($test)->toBeTruthy();

            });

        });

        context('when the given key is not present in the data array', function () {

            it('should return false', function () {

                $test = $this->session->has('key2');

                expect($test)->toBeFalsy();

            });

        });

    });

    describe('->get()', function () {

        context('when no default value is given', function () {

            context('when the given key is associated with a value', function () {

                it('should return the value associated with the given key', function () {

                    $test = $this->session->get('key1');

                    expect($test)->toEqual('value1');

                });

            });

            context('when the given key is not associated with a value', function () {

                it('should return the given default value', function () {

                    $test = $this->session->get('key2', 'default');

                    expect($test)->toEqual('default');

                });

            });

        });

        context('when a default value is given', function () {

            context('when the given key is associated with a value', function () {

                it('should return the value associated with the given key', function () {

                    $test = $this->session->get('key1', 'default');

                    expect($test)->toEqual('value1');

                });

            });

            context('when the given key is not associated with a value', function () {

                it('should return null', function () {

                    $test = $this->session->get('key2');

                    expect($test)->toBeNull();

                });

            });

        });

    });

    describe('->set()', function () {

        it('should associate the given key with the given value', function () {

            $this->session->set('key2', 'value2');

            $test = $this->session->get('key2');

            expect($test)->toEqual('value2');

        });

        it('should update the data array ref', function () {

            $this->session->set('key2', 'value2');

            $test = $this->data['key2'];

            expect($test)->toEqual('value2');

        });

    });

    describe('->unset()', function () {

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

});

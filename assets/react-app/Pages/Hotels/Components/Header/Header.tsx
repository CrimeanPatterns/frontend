import 'react-day-picker/dist/style.css';
import { SearchForm } from './Components/SearchForm';
import { Translator } from '@Services/Translator';
import React from 'react';
import classes from './Header.module.scss';

export function Header() {
    return (
        <div className={classes.contentHeader}>
            <div className={classes.contentHeaderTitleContainer}>
                <h2>{Translator.trans(/** @Desc("Award Hotel Search") */ 'award-hotel-search')} </h2>
                <p>
                    {Translator.trans(
                        /** @Desc("Find a hotel with the best points exchange offers") */ 'find-hotel-with-best-points-exchange',
                    )}
                </p>
            </div>
            <SearchForm />
        </div>
    );
}

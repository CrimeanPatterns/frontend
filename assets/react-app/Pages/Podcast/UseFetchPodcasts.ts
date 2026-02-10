import { axios } from '@Services/Axios';
import { useQuery } from '@tanstack/react-query';

export const useFetchPodcasts = () => {
    const podcastsQuery = useQuery<Podcast[]>({
        queryKey: ['podcasts'],
        queryFn: fetchPodcasts,
        retry: false,
        staleTime: Infinity,
        gcTime: Infinity,
    });

    return { podcasts: podcastsQuery.data, isLoading: podcastsQuery.isLoading, error: podcastsQuery.error };
};

export type Podcast = {
    id: string;
    season: string;
    seasonNumber: number;
    releaseDate: Date;
    title: string;
    description: string;
    imageUrl: string | null;
    audioUrl: string;
    episodeNumber: number;
    duration?: number;
    footer?: string;
    playAudio?: () => void;
};

const fetchPodcasts = async () => {
    const response = (
        await axios.get<string>('https://feeds.buzzsprout.com/266622.rss', {
            headers: {
                'Content-Type': 'application/rss+xml',
            },
        })
    ).data;

    const parser = new DOMParser();
    const xml = parser.parseFromString(response, 'application/xml');

    const podcasts: Podcast[] = Array.from(xml.querySelectorAll('item')).map(retrievePodcastData);

    return podcasts;
};

function retrievePodcastData(item: Element, index: number, itemsArray: Element[]): Podcast {
    const episodeText = item.querySelector('episode')?.textContent;
    const title = item.querySelector('title')?.textContent || 'No title';
    const season = item.querySelector('season')?.textContent || 'unknown';
    const publicDate = item.querySelector('pubDate')?.textContent;
    const description = item.querySelector('description')?.textContent?.split('Where to Find Us')[0] || '';

    const footerText = item.querySelector('description')?.textContent?.split('Where to Find Us')[1] || '';

    const imageUrl = item.querySelector('image')?.getAttribute('href') || null;
    const audioUrl = item.querySelector('enclosure')?.getAttribute('url') || '';
    const duration = item.querySelector('duration')?.textContent;

    let formattedTitle = title;
    let episodeNumber = Number(episodeText);
    const seasonNumber = Number(season);

    if (!isNaN(episodeNumber) && !isNaN(seasonNumber)) {
        if (seasonNumber === 1) {
            episodeNumber = episodeNumber - 1;
        }
    }

    if (episodeText && !isNaN(episodeNumber)) {
        if (episodeNumber > 0) {
            formattedTitle = `#${episodeNumber} ${title}`;
        }
    }

    return {
        id: String(itemsArray.length - index),
        title: formattedTitle,
        season: `Season ${season}`,
        seasonNumber,
        episodeNumber,
        releaseDate: new Date(publicDate || ''),
        description,
        imageUrl,
        audioUrl,
        duration: duration ? Number(duration) : undefined,
        footer: footerText,
    };
}

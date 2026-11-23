import {Observable, of} from 'rxjs';
import {Process, ProcessOptions, ProcessService} from './process.service';

class PreferenceProcessMock extends ProcessService {
    constructor() {
        super(null);
    }

    submit(type: string, options: ProcessOptions): Observable<Process> {
        return of({_id: 'test-process', id: 'test-process', status: 'success', async: false, type, options, messages: []});
    }
}

export const preferenceProcessMock = new PreferenceProcessMock();
